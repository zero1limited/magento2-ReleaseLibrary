<?php
namespace Zero1\ReleaseLibrary;

class EntityAlreadyExistsException extends \Exception
{
    public function __construct($entity, $idValue, $idField = 'id')
    {
        $message = sprintf(
            'Unable to create %s as one already exists with %s = %s',
            get_class($entity),
            $idField,
            $idValue
        );

        parent::_construct($message);
    }
}