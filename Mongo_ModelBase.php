<?php

class Mongo_ModelBase {
    
    public static $_mongo = null;
    public static $_collection = null;
    public static $_collectionName = null;
    
    const DB_SERVER = 'localhost';
    const DB_NAME = 'db';
    const DB_PORT = '27017';
    
    protected $id = null;
    protected $document = null;
    
    
    
    /*************************************************************************************
     * Magic methods
     *************************************************************************************/
    
    /**
     * Constructor puts full object in $document variable and assigns $id
     * @param $document
     */
    public function __construct($document = null) {
        if($document != null && isset($document['_id'])){
            $this->document = $document;
            $this->id = $this->document['_id'];
            unset($this->document['_id']);
        }
        elseif($document !== null){
            $this->document = $document;
        }
        else {
            $this->document = array();
        }
    }
    /**
     * Return object ID
     */
    public function __toString(){
        return ucfirst(static::$_collectionName) . "Object ID:" . $this->id;
    }

    /**
     * Get values like an object
     * @param string $name
     */
    public function __get($name){
        if($name == "id" || $name == "_id"){
            return $this->id->__toString();
        }
        if (false !== strpos($name, '.')) {
            return $this->_getDotNotation($name, $this->document);
        }
        return isset($this->document[$name])?$this->document[$name]:null;
    }

    /**
     * Set values like an object
     * @param string $name
     * @param mixed $val
     */
    public function __set($name, $val){
        if(false !== strpos($name, '.')){
            return $this->_setDotNotation($name, $val, $this->document);
        }
        if($val == null){
            unset($this->document[$name]);
        }
        $this->document[$name] = $val;
    }
    
    /**
     * Check if variable is set in object
     * @param string $name
     */
    public function __isset($name){
        return isset($this->document[$name]);
    }
    
    /**
     * Unset a variable in the object
     * @param $name
     */
    public function __unset($name){
        unset($this->document[$name]);
    }
    
    /**
     * Allows use of the dot notation in the __get function
     * Thanks to Ian White for this function: https://github.com/ibwhite/simplemongophp
     * @param string $fields fields with dot notation
     * @param reference $current The current part of the array working in
     */
    protected function _getDotNotation($fields, &$current){
        $i = strpos($fields, '.');
        if ($i !== false) {
            $field = substr($fields, 0, $i);
            if (!isset($current[$field])) {
                return null;
            }
            $current =& $current[$field];
            return $this->_getDotNotation(substr($fields, $i+1), $current);
        } else {
            return isset($current[$fields]) ? $current[$fields] : null;
        }
    }
    
    /**
     * Allows use of the not notation in __set function
     * Thanks to Ian White for this function: https://github.com/ibwhite/simplemongophp
     * @param string $fields
     * @param mixed $value
     * @param reference $current
     */
    protected function _setDotNotation($fields, $value, &$current){
        $i = strpos($fields, '.');
        if ($i !== false) {
            $field = substr($fields, 0, $i);
            if (!isset($current[$field])) {
                $current[$field] = array();
            }
            $current =& $current[$field];
            return $this->_setDotNotation(substr($fields, $i+1), $value, $current);
        } else {
            $current[$fields] = $value;
        }
    }
    
    /***********************************************************************************
    * Object Methods
    ************************************************************************************/
    
    /**
     * Delete the object
     */
    public function delete(){
        if($this->id != null){
            static::$_collection->remove(array("_id"=>$this->id));
        }
    }
    /**
     * Save the object with all variables that have been set
     */
    public function save(){
        if($this->id == null){
            static::insert($this->document, true);
            $this->id = $this->document['_id'];
            unset($this->document['_id']);
            return true;
        }
        else {
            return static::update(array("_id"=>$this->id), $this->document);
        }
    }
    /**
     * Do special updates to the object (incrementing, etc...)
     * @param mixed $data
     */
    public function specialUpdate($modifier, $options){
        return static::update(array("_id"=>$this->id), $modifier, $options);
    }

    /*************************************************************************************
     * Static methods
     *************************************************************************************/
    
    /**
     * Connect to mongo...
     */
    protected static function connect(){
        $mongoDns = sprintf('mongodb://%s:%s',
                self::DB_SERVER, self::DB_PORT
            );
        $connection = new Mongo($mongoDns,array("persist" => "x"));
        self::$_mongo = $connection->selectDB(self::DB_NAME);
        //If collection name isn't already set in the model
        if(static::$_collectionName == null){
            //Get collection name based on the class name. To do this, we take the class name and strip off the 
            //beginning "Model_" and the rest is the collection name. 
            $replaceableClassNameparts = array('model_'); 
            static::$_collectionName = str_replace($replaceableClassNameparts, '', strtolower(get_called_class()));
        }
        $collectionName = static::$_collectionName;
        static::$_collection = self::$_mongo->$collectionName;
    }
    /**
     * Setup db connection and init mongo collection
     */
    public static function init(){
        if(self::$_mongo == null){
            self::connect();
        }
    }
    
    /**
     * Load object by ID
     * @param $_id
     */
    public static function load($_id){
        $object = static::findOne(array("_id"=>new MongoId($_id)));
        if($object === null){
            return false;
        }
        else{
            return $object;
        }
    }
    
    /**
     * Find all records in a collection
     */
    public static function findAll(){
        return static::find();
    }
    
    /**
     * Get one record
     */
    public static function findOne($conditionalArray = null, $fieldsArray = null, $sort = null){
        $className = get_called_class();
        $document = static::getCursor($conditionalArray, $fieldsArray, true);
        if($document == null){
            return null;
        }
        $object = new $className($document);
        return $object;
    }
    
    /**
     * Query the database for documents in the collection
     * @param array $conditionalArray 
     * @param array $fieldsArray 
     * @param array $sort
     * @param int $limit
     */
    public static function find($conditionalArray = null, $fieldsArray = null, $sort = null, $limit = null){
        $cursor = static::getCursor($conditionalArray, $fieldsArray);
        if($limit != null){
            $cursor = $cursor->limit($limit);
        }
        if($sort != null){
            $cursor = $cursor->sort($sort);
        }
        $className = get_called_class();
        $objectArray = array();
        foreach($cursor as $document){
            $objectArray[] = new $className($document);
        }
        return $objectArray;
    }
    
    /**
     * Count by query array
     * @param array $conditionalArray
     */
    public static function count($conditionalArray = null){
        $cursor = static::getCursor($conditionalArray);
        return $cursor->count();
    }
    
    /**
     * Create cursor by query document
     * @param array $conditionalArray
     * @param array $fieldsArray
     */
    protected static function getCursor($conditionalArray = null, $fieldsArray = null, $one=false){
        static::init();
        if($conditionalArray == null) $conditionalArray = array();
        if($fieldsArray == null) $fieldsArray = array();
        if($one){
            return static::$_collection->findOne($conditionalArray, $fieldsArray);
        }
        $cursor = static::$_collection->find($conditionalArray, $fieldsArray);
        return $cursor;
    }
    
    /**
     * 
     * Enter description here ...
     * @param array $data
     * @param bool $safe // Set true if you want to wait for database response...
     * @param bool $fsync
     */
    public static function insert($data, $safe = false, $fsync = false){
        static::init();
        $options = array();
        if($safe){
            $options['safe'] = true;
        }
        if($fsync){
            $options['fsync'] = true;
        }
        return static::$_collection->insert($data, $options);
    }
    
    /**
     * Do a batch insert into the collection
     * @param array $data
     */
    public static function batchInsert($data){
        static::init();
        return static::$_collection->batchInsert($data);
    }

    public static function update($criteria, $update, $options = array()){
        static::init();
        return static::$_collection->update($criteria, $update, $options);
    }
}