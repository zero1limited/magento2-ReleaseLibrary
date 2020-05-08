<?php
namespace Zero1\ReleaseLibrary;

use Exception;
use Magento\Variable\Model\VariableFactory;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Framework\Module\Dir;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\PageRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Zero1\ReleaseLibrary\EntityAlreadyExistsException;
use \Psr\Log\LoggerInterface;

class Utility
{
    /** @var \Magento\Variable\Model\VariableFactory **/
    protected $customVariableFactory;
    
    /** @var CategoryManagementInterface */
    protected $categoryManagement;  
    
    /** @var Dir\Reader */
    private $reader;

    /** @var BlockRepositoryInterface */
    private $blockRepository;
    /** @var BlockInterfaceFactory */
    private $blockInterfaceFactory;

    /** @var \Magento\Cms\Model\PageRepository */
    protected $pageRepository;
    /** @var \Magento\Cms\Model\PageFactory */
    protected $pageFactory;

    
    /** @var LoggerInterface */
    private $logger;


    public function __construct(
        VariableFactory $customVariableFactory,
        CategoryManagementInterface $categoryManagement,
        Dir\Reader $reader,
        BlockRepositoryInterface $blockRepository,
        BlockInterfaceFactory $blockInterfaceFactory,
        PageRepository $pageRepository,
        PageFactory $pageFactory,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->customVariableFactory = $customVariableFactory;
        $this->categoryManagement = $categoryManagement;
        $this->reader = $reader;
        $this->blockRepository = $blockRepository;
        $this->blockInterfaceFactory = $blockInterfaceFactory;
        $this->pageRepository = $pageRepository;
        $this->pageFactory = $pageFactory;
        $this->logger = $logger;
    }

    public function createCustomVariable(
        $code,
        $name,
        $htmlValue,
        $plainValue,
        $updateIfExists = true
    ){
        $customVariable = $this->customVariableFactory->create();
        $customVariable->loadByCode($code);

        if($customVariable->getId() && !$updateIfExists){
            throw new EntityAlreadyExistsException($customVariable, $code, 'code');
        }

        $customVariable->setCode($code)
            ->setName($name)
            ->setHtmlValue($htmlValue)
            ->setPlainValue($plainValue)
            -> save();
    }
    
     /**
     * Move Category
     *
     * @return CategoryTreeInterface
     */
    public function moveCategory($categoryId,$parentId,$afterId)
    {
		$this->logger->alert('moveCategory', array()) ; 
        $isCategoryMoveSuccess = false;
        try {
            $isCategoryMoveSuccess = $this->categoryManagement->move($categoryId, $parentId, $afterId);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
        return $isCategoryMoveSuccess;
    }
    
    
    public function getBlockSourceDirectory()
    {    	
		$this->logger->alert('getBlockSourceDirectory', array()) ; 
        return sprintf(
            '%s/%s',
            $this->reader->getModuleDir(Dir::MODULE_VIEW_DIR, 'Zero1_ClientSetup'),
            'block_source'
        );
    }

    public function getPageSourceDirectory()
    {
        $this->logger->alert('getPageSourceDirectory', array()) ;
        return sprintf(
            '%s/%s',
            $this->reader->getModuleDir(Dir::MODULE_VIEW_DIR, 'Zero1_ClientSetup'),
            'page_source'
        );
    }
    
    public function createBlocksFromDir($source)
    {
        $blocks = array_diff(scandir($source), ['..', '.']);
        foreach ($blocks as $block) {
            $path = $source . '/' . $block;
            if (is_dir($path)) {
                $this->createBlocksFromDir($path);
                continue;
            }

            $id = pathinfo($block, PATHINFO_FILENAME);
            $title = str_replace('-', ' ', $id);
            $contents = file_get_contents($path);
            $this->makeBlock($id, $title, $contents);
        }
    }

    public function createPagesFromDir($source)
    {
        $blocks = array_diff(scandir($source), ['..', '.']);
        foreach ($blocks as $block) {
            $path = $source . '/' . $block;
            if (is_dir($path)) {
                $this->createPagesFromDir($path);
                continue;
            }

            $id = pathinfo($block, PATHINFO_FILENAME);
            $title = str_replace('-', ' ', $id);
            $contents = file_get_contents($path);
            $this->makeBlock($id, $title, $contents);
        }
    }
    
    private function makeBlock(
        string $identifier,
        string $title,
        string $content,
        array $stores = [0]
    ) {
        try {
            $block = $this->blockRepository->getById($identifier);
        } catch (NoSuchEntityException $exception) {
            /** @var BlockInterface $block */
            $block = $this->blockInterfaceFactory->create();
            $this->logger->alert('makeBlock '.$title, array()) ;

            $block->setTitle($title)
                ->setIdentifier($identifier)
                ->setIsActive(true)
                ->setData('stores', $stores);
        }

        $block->setContent($content);


        try {
            $this->logger->alert('saveBlock '.$title, array()) ;
            $this->blockRepository->save($block);
        } catch (NoSuchEntityException $exception) {
            $this->logger->alert('saveBlock ERROR '.$exception, array()) ;


        }


    }


    private function makePage(
        string $identifier,
        string $title,
        string $content,
        array $stores = [0]
    ) {
        try {
            $page = $this->pageRepository->getById($identifier);
        } catch (NoSuchEntityException $exception) {
            /** @var \Magento\Cms\Model\Page $page */
            $this->logger->alert('makePage '.$title, array()) ;

            $page = $this->pageFactory->create();
            $page->setTitle($title)
                ->setIdentifier($identifier)
                ->setPageLayout('cms-page')
                ->setIsActive(true)
                ->setContent($content)
                ->setData('stores', $stores);
        }

        $page->setContent($content);
        $this->blockRepository->save($page);
    }
}
