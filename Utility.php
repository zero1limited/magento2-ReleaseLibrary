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
use \Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;

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

    /** @var ConfigWriterInterface */
    protected $configWriter;

    /** @var string */
    protected $sourceModule = 'Zero1_ClientSetup';

    public function __construct(
        VariableFactory $customVariableFactory,
        CategoryManagementInterface $categoryManagement,
        Dir\Reader $reader,
        BlockRepositoryInterface $blockRepository,
        BlockInterfaceFactory $blockInterfaceFactory,
        PageRepository $pageRepository,
        PageFactory $pageFactory,
        ConfigWriterInterface $configWriter,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->customVariableFactory = $customVariableFactory;
        $this->categoryManagement = $categoryManagement;
        $this->reader = $reader;
        $this->blockRepository = $blockRepository;
        $this->blockInterfaceFactory = $blockInterfaceFactory;
        $this->pageRepository = $pageRepository;
        $this->pageFactory = $pageFactory;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
    }

    /**
     * Create a custom variable
     * @param $code string
     * @param $name string
     * @param $htmlValue string
     * @param $plainValue string
     * @param bool $updateIfExists
     * @throws \Zero1\ReleaseLibrary\EntityAlreadyExistsException
     */
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
     * Move a category
     * @param $categoryId int
     * @param $parentId int
     * @param null|int $afterId
     * @return bool
     * @throws Exception
     */
    public function moveCategory($categoryId,$parentId,$afterId = null)
    {
	$this->logger->alert('moveCategory', array()) ; 
        $isCategoryMoveSuccess = false;
        try {
            $isCategoryMoveSuccess = $this->categoryManagement->move($categoryId, $parentId, $afterId);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage().' '.$categoryId);
        }
        return $isCategoryMoveSuccess;
    }

    /**
     * Set the "source module"
     * This is the module that contains files to be imported
     * e.g cms block html in a file instead of in upgrade code
     * @param string $sourceModule
     * @return $this
     */
    public function setSourceModule($sourceModule)
    {
        $this->sourceModule = $sourceModule;
        return $this;
    }

    /**
     * Get the cms block source directory
     * @return string
     */
    public function getBlockSourceDirectory()
    {    	
		$this->logger->alert('getBlockSourceDirectory', []);
        return sprintf(
            '%s/%s',
            $this->reader->getModuleDir(Dir::MODULE_VIEW_DIR, $this->sourceModule),
            'block_source'
        );
    }

    /**
     * Get the cms page source directory
     * @return string
     */
    public function getPageSourceDirectory()
    {
        $this->logger->alert('getPageSourceDirectory', []);
        return sprintf(
            '%s/%s',
            $this->reader->getModuleDir(Dir::MODULE_VIEW_DIR, $this->sourceModule),
            'page_source'
        );
    }

    /**
     * Create all blocks from specified directory
     * @param string $source
     */
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

    /**
     * Create all pages from specified directory
     * @param string $source
     */
    public function createPagesFromDir($source)
    {
        $pages = array_diff(scandir($source), ['..', '.']);
        foreach ($pages as $page) {
            $path = $source . '/' . $page;
            if (is_dir($path)) {
                $this->createPagesFromDir($path);
                continue;
            }

            $id = pathinfo($page, PATHINFO_FILENAME);
            $title = str_replace('-', ' ', $id);
            $contents = file_get_contents($path);
            $this->makePage($id, $title, $contents);
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
                ->setPageLayout('1column')
                ->setIsActive(true)
                ->setContent($content)
                ->setData('stores', $stores);
        }

        $page->setContent($content);
        $this->pageRepository->save($page);
    }

    /**
     * Set config
     * @param array $pathAndValue
     * must be an array of arrays, each sub array must contain between 2 - 4 elements
     * index 0: config path (mandatory)
     * index 1: value (mandatory)
     * index 2: scope (optional - if not set will fall back to $defaultScope)
     * index 3: scope id (optional - if not set will fall back to $defaultScopeId)
     * @param string $defaultScope
     * @param int $defaultScopeId
     * @return Utility
     * @throws \InvalidArgumentException
     */
    public function setConfig($pathAndValue, $defaultScope = 'default', $defaultScopeId = 0)
    {
        foreach($pathAndValue as $configRow){
            if(!isset($configRow[0]) || !isset($configRow[1])){
                throw new \InvalidArgumentException('You must provide at least two elements, you provided: '.json_encode($configRow));
            }
            $path = $configRow[0];
            $value = $configRow[1];
            $scope = isset($configRow[2])? $configRow[2] : $defaultScope;
            $scopeId = isset($configRow[3])? $configRow[3] : $defaultScopeId;
            $this->logger->debug('set config', [
                'path' => $path,
                'value' => $value,
                'scope' => $scope,
                'scope_id' => $scopeId
            ]);
            $this->configWriter->save($path, $value, $scope, $scopeId);
        }
        return $this;
    }
}
