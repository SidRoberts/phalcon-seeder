Sid\Phalcon\Seeder
==================

Database seeder component for Phalcon.



## Installing ##

Install using Composer:

```
{
	"require": {
		"sidroberts/phalcon-seeder": "dev-master"
	}
}
```



## Example ##

### DI ###

```php
$di->set(
	"seeder",
	function () {
		$seeder = new \Sid\Phalcon\Seeder\Seeder();
		
		return $seeder;
	},
	true
);
```

### Model ###

Model metadata is stored in the Model class using [annotations](https://docs.phalconphp.com/en/latest/reference/annotations.html).

Class annotations are used to store indexes, references and any initial data you want to import. Property annotations are used to store metadata about individual columns:

```php
/**
 * @Index("emailAddress", ["emailAddress"])
 * 
 * @Reference("Users_userID", {referencedTable="Posts", columns=["userID"], referencedColumns=["userID"]})
 *
 * @Data({userID=1, emailAddress="sid1@sidroberts.co.uk", password="S3CR3T"})
 * @Data({userID=2, emailAddress="sid2@sidroberts.co.uk", password="P4SSW0RD"})
 *
 * @DataJson("http://my-website.com/path/to/some/data.json")
 */
class Users extends \Phalcon\Mvc\Model
{
    /**
     * @Primary
     * @Identity
     * @Column(type="biginteger",nullable=false)
     */
    public $userID;
    
    /**
     * @Column(type="varchar",size=255,nullable=false)
     */
    public $emailAddress;
    
    /**
     * @Column(type="varchar",size=100,nullable=false)
     */
    public $password;
}
```

Indexes and references use the same format as creating [`Phalcon\Db\Index`](https://docs.phalconphp.com/en/latest/api/Phalcon_Db_Index.html) and [`Phalcon\Db\Reference`](https://docs.phalconphp.com/en/latest/api/Phalcon_Db_Reference.html) instances.

Property annotations use the same format as the [annotations metadata strategy](https://docs.phalconphp.com/en/latest/reference/models-metadata.html#annotations-strategy). As such it may be useful to use the annotations strategy for the models metadata in your DI:

```php
$di->set(
	"modelsMetadata",
	function () {
		$modelsMetadata = new \Phalcon\Mvc\Model\MetaData\Memory();

        $modelsMetadata->setStrategy(
            new \Phalcon\Mvc\Model\MetaData\Strategy\Annotations()
        );
        
        return $modelsMetadata;
    },
    true
);
```

### Task ###

You'll need to pass an array of all your models. Indexes and references are handled after all the tables are created so you don't need to worry about their order. However, any models that have initial data need to be ordered so that any data it relies on already exists.

```php
class SeederTask extends \Phalcon\Cli\Task
{
    protected function getModels()
    {
        $models = [
            new \Sid\Pomelo\Models\Posts(),
            new \Sid\Pomelo\Models\Users(),
            new \Sid\Pomelo\Models\System\CronJobs(),
        ];
        
        return $models;
    }


    
    public function seedAction()
    {
        $this->seeder->seed(
            $this->getModels()
        );
    }
    
    public function dropAction()
    {
        $this->seeder->drop(
            $this->getModels()
        );
    }
}
```



## Events ##

| Name                               |
| ---------------------------------- |
| seeder:beforeCreateTable           |
| seeder:afterCreateTable            |
| seeder:beforeCreateModelIndexes    |
| seeder:afterCreateModelIndexes     |
| seeder:beforeCreateModelReferences |
| seeder:afterCreateModelReferences  |
| seeder:beforeCreateModelData       |
| seeder:afterCreateModelData        |
| seeder:beforeDrpoModelReferences   |
| seeder:afterDropModelReferences    |
| seeder:beforeTruncateTable         |
| seeder:afterTruncateTable          |
| seeder:beforeDropTable             |
| seeder:afterDropTable              |
