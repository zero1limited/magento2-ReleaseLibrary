<?php
namespace Zero1\ReleaseLibrary;

use Magento\Variable\Model\VariableFactory;
use Zero1\ReleaseLibrary\EntityAlreadyExistsException;

class Utility
{
    /** @var \Magento\Variable\Model\VariableFactory **/
    protected $customVariableFactory;

    public function __construct(
        VariableFactory $customVariableFactory
    ){
        $this->customVariableFactory = $customVariableFactory;
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
}