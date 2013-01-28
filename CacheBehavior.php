<?php

/*
Copyright (c) 2013 Ashe Avenue Development

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class CacheBehavior extends CBehavior {

    var $theme;
    var $model;

    // constants
    var $KEYLIST_NAME   = "VMP_KEY_LIST";
    var $TTL_DEFAULT    = 600; //10 minute default timer
    var $KEYLIST_TTL    = 0; //keylist lives forever


    // Save data to the cache
    //
    // Takes:
    //      data - the value to be set in the cache
    //      key - the key used to save the data
    //      ttl (Optional) - the time-to-live (in seconds) for the cached data
    //      cacheEntity (Optional) - the entity containing the key
    //
    // Returns:
    //      true if data was successfully saved
    //      false if data couldn't be saved
    
    public function cset($data, $key, $ttl = false, $cacheEntity = false) {
        // exit if a cache isn't available/enabled
        if(!self::cacheEnabled()) return false;
        
        // get the time-to-live
        $ttl = isset($ttl) ? $ttl : $this->TTL_DEFAULT;
        
        // add key to to the keyList
        // will do nothing if key is already in list
        $this->cacheAddKeyToKeyList($key, $cacheEntity);

        // get the key name
        $keyName = $this->cacheKey($key, $cacheEntity); 

        // set the data in the cache according to the key.
        return Yii::app()->cache->set($keyName, $data, $ttl);
    }




    // Get a key's data out of the cache
    //
    // Takes:
    //      key - the key used to retrieve the data
    //      cacheEntity (Optional) - the entity containing the key
    //
    // Returns:
    //      the data that corresponds to the key
    
    public function cget($key, $cacheEntity = false) {
        // exit if a cache isn't available/enabled
        if(!self::cacheEnabled()) return false;
        
        // exit if the key isn't found in the keyList
        if(!$this->cacheKeyExistsInKeyList($key, $cacheEntity)) return false;
        
        // get the keyName
        $keyName = $this->cacheKey($key, $cacheEntity); 
        
        // return the value from the cache
        return $value = Yii::app()->cache->get($keyName);
    }




    // Delete a single data element from the cache
    //
    // Takes:
    //      key - the key used to identify the data element
    //      cacheEntity (Optional) - the entity containing the key
    //
    // Returns:
    //      true if the data element was successfully deleted
    //      false if the data element could not be deleted
    
    public function cdelete($key, $cacheEntity=false) {
        // exit if a cache isn't available/enabled
        if(!self::cacheEnabled()) return false;
        
        // exit if the key isn't found in the keyList
        if(!$this->cacheKeyExistsInKeyList($key, $cacheEntity)) return false;
        
        // first delete the key from the keyList
        $this->cacheDeleteKeyFromKeyList($key, $cacheEntity);
         
        // now delete the data itself
        $keyName = $this->cacheKey($key, $cacheEntity);
        return Yii::app()->cache->delete($keyName);

    }




    // Purge all data in the cache for an entity
    //
    // Takes:
    //      cacheEntity (Optional) - the entity to be purged
    //
    // Returns:
    //      nothing (void)
    
    public function cpurge($cacheEntity=false) {
        // exit if a cache isn't available/enabled
        if(!self::cacheEnabled()) return false;
        
        // get the keyList name
        $keyListName = $this->cacheKeyListName($cacheEntity);
        
        // get the keyList
        $keylist = Yii::app()->cache->get($keyListName);
        
        if($keylist) {
            //get all keys pertaining to this entity
            $keys = explode("|", $keyList);
            
            //loop through the keys
            foreach($keys as $listedKey=>$val) {
                //get the key name
                $keyName = $this->cacheKey($val, $cacheEntity);
                
                //delete the data for this key plus the key itself
                $this->cdelete($keyName, $cacheEntity);
            }
            
            //delete the actual entity
            Yii::app()->cache->delete($keyListName);
        }

        //return nothing
        return; 
    }




    //
    // PRIVATE/INTERNAL FUNCTIONS
    //




    // Add a key to the keyList.
    // Create the keyList if it doesn't currently exist.
    // Do nothing if the keyList already contains the key.
    //
    // Takes:
    //      key - the key to be added to the keylist
    //      cacheEntity - the entity containing the key
    //
    // Returns:
    //      nothing (void)
    
    private function cacheAddKeyToKeyList($key, $cacheEntity) {
        // get the keyListName
        $keyListName = $this->cacheKeyListName($cacheEntity);
        
        // get the keylist
        $keyList = Yii::app()->cache->get($keyListName);
        
        if($keyList && $keyList != "|") {
            // return if the key already exists in the keyList
            if($this->ckeylistCheck($key, $cacheEntity)) return;
            
            // otherwise update the keyList with the new key
            $keyList = $keyList . "|" . $key; 
            
            // save the keylist to the cache
            if(!Yii::app()->cache->set($keyListName, $keyList, $this->KEYLIST_TTL))
                throw new Exception("CacheBehavior Exception: Couldn't create keyList");
        } else {
            // the keylist didn't exist, so create it
            if(!Yii::app()->cache->set($keyListName, $key, $this->KEYLIST_TTL))
                throw new Exception("CacheBehavior Exception: Couldn't create keyList");
        }
    }




    // Checks to see if a key already exists in a keyList
    //
    // Takes:
    //      key - the key to be checked for in the keylist
    //      cacheEntity - the entity containing the key
    //
    // Returns:
    //      true if key exists in keyList
    //      false if key doesn't exist in keyList
    
    private function cacheKeyExistsInKeyList($key, $cacheEntity) {
        // get the keyList
        $keyList = Yii::app()->cache->get($this->cacheKeyListName($cacheEntity));
        
        // only 
        if($keyList) {
            // get the keys in the keyList
            $keys = explode("|", $keyList);
            
            // loop through keys to see if it exists. Return true if it's found.
            foreach($keys as $listedKey=>$val) {
                if ($val === $key) return true;
            }
        }
        
        // otherwise return false
        return false;
        
    }




    // Delete a key from a keylist
    //
    // Takes:
    //      key - the key to be deleted from the keylist
    //      cacheEntity - the entity containing the key
    //
    // Returns:
    //      nothing (void)
    
    private function cacheDeleteKeyFromKeyList($key, $cacheEntity) {
        // get the keyListName
        $keyListName = $this->cacheKeyListName($cacheEntity);
        
        // get the keylist
        $keyList = Yii::app()->cache->get($keyListName);
        
        // only act if the keylist is found
        if($keyList) {
            
            // delete key from list without converting to array
            // remove the key, then get rid of double pipes
            $keyList = str_replace($keyList, $key, '');
            $keyList = str_replace($keyList, "||","|");

            // reset the keylist by adding it back to the cache
            Yii::app()->cache->set($keyListName, $keylist, $this->KEYLIST_TTL); 
            // note this resets the TTL on delete. MAY NOT WANT THIS BEHAVIOR
        }
        
        // return nothing
        return;
    }




    // Generates the name of the key list for an instance and entity
    //
    // Takes:
    //      cacheEntity (Optional) -- Used if getting a key list that pertains to a specific entity
    //
    // Returns:
    //      string containing the full instance, entity, and key
    public function cacheKeyListName($cacheEntity=false)
    {
        return self::cacheInstance() . "." . $this->cacheEntity($cacheEntity) . "." . $this->KEYLIST_NAME;
    }




    // Generate cache key
    // Forces lowercase.
    //
    // Takes:
    //      cacheEntity (Optional) -- Used if getting a key that pertains to a specific entity
    //
    // Returns:
    //      string containing the full instance, entity, and key
    
    private function cacheKey($key, $cacheEntity=false) {
        $cacheKey = self::cacheInstance() . "." . $this->cacheEntity($cacheEntity) . "." . $key;
        return $cacheKey;
    }




    // Generate entity name and id -- used for filing items in the cache appropriately.
    // Forces lowercase.
    //
    // Takes:
    //      attr (Optional) -- Used if another attribute besides the entity's id is used as a reference
    //
    // Returns:
    //      string containing the entity name and ID/attr
    
    private function cacheEntity($attr=false) {
        // get the name of the entity using this behavior
        $owner = $this->getOwner();
        
        // $attr can only be empty or false if we plan to use the entity's id
        if($attr===false) {
            // throw Exceptions if we don't have an owner or an owner ID
            if(!isset($owner)) throw new Exception("CacheBehavior requires a model instance when no ID is explicitly set.");
            if(!isset($owner->id)) throw new Exception("Cannot cache an unsaved object");
            
            // generate the entity using the owner's id
            $entity = get_class($owner) . "." . $owner->id; 
        } else {
            // generate the entity using the attribute that's been passed in.
            $entity = get_class($owner) . "." . $attr;
        }

        // force lowercase.
        return strtolower($entity);
    }




    // Generate cache instance name based on current site/theme.
    // Forces lowercase and removes dashes and dots.
    //
    // Takes:
    //      Nothing
    //
    // Returns:
    //      string containing the cache instance name
    
    private function cacheInstance() {
        // make sure we have a theme set. default to APP_SITE env variable if not set.
        if(!isset($this->theme)) { 
            $this->theme = (getenv("APP_SITE") == "admin") ? Yii::app()->params['frontend_theme'] : getenv("APP_SITE"); 
        }

        // remove dashes and dots, then force lowercase
        $instance = strtolower(str_replace(array(".", "-"), "", $this->theme));
        
        // throw an Exception if the user doesn't have a theme set
        if(!$instance) throw new Exception("Cannot call cacheInstance function without a theme or APP_SITE setting");

        //return the cache instance name
        return $instance;
    }




    // Determine if the Yii cache is enabled
    //
    // Takes:
    //      Nothing
    //
    // Returns: 
    //      true if Yii cache is enabled
    //      false if Yii cache is disabled
    
    private function cacheEnabled() {
        return isset(Yii::app()->components['cache']) ? true : false;
    }

}
