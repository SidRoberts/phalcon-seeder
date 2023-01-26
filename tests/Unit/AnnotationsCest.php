<?php

namespace Tests\Unit;

use Phalcon\Db\Column;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\FactoryDefault\Cli as CliFactoryDefaultDi;
use Sid\Phalcon\Seeder\Annotations;
use Tests\Support\UnitTester;
use Tests\Support\Users;

class AnnotationsCest
{
    protected DiInterface $di;



    public function _before(): void
    {
        Di::reset();

        $this->di = new CliFactoryDefaultDi();
    }



    public function getColumns(UnitTester $I): void
    {
        $user = new Users();

        $annotations = new Annotations($user);

        $columns = $annotations->getColumns();

        $I->assertCount(
            3,
            $columns
        );



        $userIdColumn = $columns[0];

        $I->assertEquals(
            "userID",
            $userIdColumn->getName()
        );

        $I->assertTrue(
            $userIdColumn->isPrimary()
        );

        $I->assertTrue(
            $userIdColumn->isNotNull()
        );

        $I->assertTrue(
            $userIdColumn->isAutoIncrement()
        );

        $I->assertEquals(
            Column::TYPE_BIGINTEGER,
            $userIdColumn->getType()
        );



        $emailAddressColumn = $columns[1];

        $I->assertEquals(
            "emailAddress",
            $emailAddressColumn->getName()
        );

        $I->assertFalse(
            $emailAddressColumn->isPrimary()
        );

        $I->assertTrue(
            $emailAddressColumn->isNotNull()
        );

        $I->assertFalse(
            $emailAddressColumn->isAutoIncrement()
        );

        $I->assertEquals(
            255,
            $emailAddressColumn->getSize()
        );

        $I->assertEquals(
            Column::TYPE_VARCHAR,
            $emailAddressColumn->getType()
        );



        $passwordColumn = $columns[2];

        $I->assertEquals(
            "password",
            $passwordColumn->getName()
        );

        $I->assertFalse(
            $passwordColumn->isPrimary()
        );

        $I->assertTrue(
            $passwordColumn->isNotNull()
        );

        $I->assertFalse(
            $passwordColumn->isAutoIncrement()
        );

        $I->assertEquals(
            100,
            $passwordColumn->getSize()
        );

        $I->assertEquals(
            Column::TYPE_VARCHAR,
            $passwordColumn->getType()
        );
    }



    public function getIndexes(UnitTester $I): void
    {
        $user = new Users();

        $annotations = new Annotations($user);

        $indexes = $annotations->getIndexes();

        $I->assertCount(
            1,
            $indexes
        );

        $index = $indexes[0];

        $I->assertEquals(
            "emailAddress",
            $index->getName()
        );

        $I->assertEquals(
            [
                "emailAddress",
            ],
            $index->getColumns()
        );
    }



    public function getReferences(UnitTester $I): void
    {
        $user = new Users();

        $annotations = new Annotations($user);

        $references = $annotations->getReferences();

        $I->assertCount(
            1,
            $references
        );

        $reference = $references[0];

        $I->assertEquals(
            "Users_userID",
            $reference->getName()
        );

        $I->assertEquals(
            "Posts",
            $reference->getReferencedTable()
        );

        $I->assertEquals(
            [
                "userID",
            ],
            $reference->getColumns()
        );

        $I->assertEquals(
            [
                "userID",
            ],
            $reference->getReferencedColumns()
        );
    }



    public function getInitialData(UnitTester $I): void
    {
        $user = new Users();

        $annotations = new Annotations($user);

        $initialData = $annotations->getInitialData();



        $I->assertCount(
            2,
            $initialData
        );



        $expected = [
            [
                "userID"       => 1,
                "emailAddress" => "sid1@sidroberts.co.uk",
                "password"     => "S3CR3T",
            ],

            [
                "userID"       => 2,
                "emailAddress" => "sid2@sidroberts.co.uk",
                "password"     => "P4SSW0RD",
            ],
        ];

        $I->assertEquals(
            $expected,
            $initialData
        );
    }
}
