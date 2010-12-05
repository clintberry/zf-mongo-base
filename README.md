Mongo Base Class for Zend Framework
===================================

About
-----

This is a base class for MongoDB written for Zend Framework models. It is for
use where a model file will be created for a collection in Mongo. It gives an
Active Directory way of manipulating objects.

_This class uses late static binding, so PHP 5.3 is required_

Let me know if this is file is useful for you! 

Installation
------------
* Create a directory in your application's Library directory named "Mongo" and place the ModelBase.php file in it.
* Change the connection settings in ModelBase.php file to match your MongoDB settings.
* Create a model class that inherits the base class

Example Usage
-------------
I created a Visitor model in Zend Framework for basic analytics:

    class Model_Visitor extends Mongo_ModelBase {
        // You can set the collection name explicitly, or it will auto generate 
        // by the name of your model (e.g. Model_Visitor would be the 
        // "visitor" collection
        public static $_collectionName = "visitor";
    }

Then when I need to create a new visitor I simply create a new instance 
of my model:

    $myVisitor = new Model_Visitor();
    
    $myVisitor->ipAddress = "55.55.55.55";
    $myVisitor->browser = "Firefox";
    $myVisitor->save();


Use the dot notation to access and set nested elements
These two commands do the same thing:

    $myVisitor->{'referrer.url'} = "www.google.com";
    $myVisitor->referrer = array("url"=>"www.google.com");
   
   
__Load one visitor__

    $myLoadedVisitor = Model_Visitor::findOne();


__Load all visitors__

    $visitorArray = Model_Visitor::find();


__Find visitors by IP__

    $visitorArray = Model_Visitor::find(array("ip"=>"55.5.5.5"));

License Information
-------------------

Copyright 2010 [Clint Berry](http://clintberry.com)

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at [http://www.apache.org/licenses/LICENSE-2.0](http://www.apache.org/licenses/LICENSE-2.0)

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
   
   
