Yii-CacheBehavior
=================

CacheBehavior is an intelligent caching system exposed as a Yii model behavior. It abstracts the chores of setting, getting, deleting, and purging context-sensitive data to and from your Yii application's Memcache instance.  
#Structure

Rather than storing cache information outside of the cache, each entity (for example, a model object) keeps a corresponding list of keys. Each key is then used as a reference to a specific data element within the cache.

    Keylist : $cacheInstance.$cacheEntity.KEY_LIST   = "datakey1|datakey2|datakey3..." 
    Data    : $cacheInstance.$cacheEntity.datakey1   = $data;

A "hit" occurs when 2 things happen:
* the requested key is found in the KEY_LIST
* the key from the KEY_LIST is succesfully located.

A "miss" can occur at one of two points:
* the requested KEY_LIST is not found (cost: 1 cache read)
* the requested key from the KEY_LIST is not found (cost: 2 cache reads)

Invalidating an item in the cache is as simple as deleting the key from the KEY_LIST. 

Public Functions
================

##cget
Get a value for a key in the cache.

Example: 
```php
$key = $this->cget('fragCacheKey');
```

##cset
Set a value for a key in the cache.

Example: 
```php
$this->cget($this->id, 'fragCacheKey');
```

##cdelete
Delete a value from an entity's keylist, then delete the data pertaining to a key.

Example: 
```php
$this->cdelete('fragCacheKey');
```

##cpurge
Remove an entire entity from the keylist, and loop through all of the keys in the keylist and delete all of the data corresponding to each key.

Example: 
```php
$this->cpurge();
```

Usage
================

Add to your model's behaviors closure:
```php
    function behaviors() {
        return array('CacheBehavior' => array('class' => 'application.behaviors.CacheBehavior'));
    }
```

In your model, you can get and set any type of key you wish to cache. In this case we store a fragCache key that'll be used in a view to store page fragments in the cache:
```php
    public function fragCacheKey() {
        $key = $this->cget('fragCacheKey');
        if(!$key) {
            $key = "articleinfo" . $this->id . str_replace(array(":", " ", "-"), "", $this->date_modified);
            $this->cset($key, 'fragCacheKey');
        }
        return $key;
    }
```

In your view you can then cache based on the fragCacheKey created by your model:
```php
<?php if($this->beginCache($model->fragCacheKey(), array('duration'=>$cacheTTL))) { ?>
  
  [content to cache goes here]
  
<?php $this->endCache(); } ?> 
```

