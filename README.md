Yii-CacheBehavior
=================

A Yii Behavior that abstracts the chore of setting, getting, deleting, and purging complex MemCache value trees

Public Functions
================

###cget
Get a value for a key in the cache.

###cset
Set a value for a key in the cache.

###cdelete
Delete a value from an entity's keylist, then delete the data pertaining to a key.

###cpurge
Remove an entire entity from the keylist, and loop through all of the keys in the keylist and delete all of the data corresponding to each key.

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
            $key = "articleinfo" . $this->id . str_replace(array(":", " ", "-"), "", $this->modified);
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

