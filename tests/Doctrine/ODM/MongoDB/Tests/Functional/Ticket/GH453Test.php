<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use function array_values;
use function get_class;
use function sprintf;

class GH453Test extends BaseTest
{
    public function testHashWithStringKeys()
    {
        $hash = ['a' => 'x', 'b' => 'y', 'c' => 'z'];

        $doc       = new GH453Document();
        $doc->hash = $hash;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObjectAndValue($hash, $doc->id, 'hash');

        // Check that the value is hydrated properly
        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertSame($hash, $doc->hash);

        $this->dm->clear();

        // Check that the value is changed properly
        unset($hash['b']);
        $doc       = $this->dm->merge($doc);
        $doc->hash = $hash;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObjectAndValue($hash, $doc->id, 'hash');
    }

    public function testHashWithNumericKeys()
    {
        $hash = [0 => 'x', 1 => 'y', 2 => 'z'];

        $doc       = new GH453Document();
        $doc->hash = $hash;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObjectAndValue($hash, $doc->id, 'hash');

        // Check that the value is hydrated properly
        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertSame($hash, $doc->hash);

        $this->dm->clear();

        // Check that the value is changed properly
        unset($hash[1]);
        $doc       = $this->dm->merge($doc);
        $doc->hash = $hash;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonObjectAndValue($hash, $doc->id, 'hash');
    }

    public function testCollection()
    {
        $col = ['x', 'y', 'z'];

        $doc          = new GH453Document();
        $doc->colPush = $col;
        $doc->colSet  = $col;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArrayAndValue($col, $doc->id, 'colPush');
        $this->assertBsonArrayAndValue($col, $doc->id, 'colSet');

        // Check that the value is hydrated properly
        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertSame($col, $doc->colPush);
        $this->assertSame($col, $doc->colSet);

        $this->dm->clear();

        // Check that the value is changed properly
        unset($col[1]);
        $doc          = $this->dm->merge($doc);
        $doc->colPush = $col;
        $doc->colSet  = $col;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArrayAndValue($col, $doc->id, 'colPush');
        $this->assertBsonArrayAndValue($col, $doc->id, 'colSet');
    }

    public function testEmbedMany()
    {
        $colPush     = new ArrayCollection([
            new GH453EmbeddedDocument(),
            new GH453EmbeddedDocument(),
            new GH453EmbeddedDocument(),
        ]);
        $colSet      = $colPush->map(static function ($v) {
            return clone $v;
        });
        $colSetArray = $colPush->map(static function ($v) {
            return clone $v;
        });
        $colAddToSet = $colPush->map(static function ($v) {
            return clone $v;
        });

        $doc                    = new GH453Document();
        $doc->embedManyPush     = $colPush;
        $doc->embedManySet      = $colSet;
        $doc->embedManySetArray = $colSetArray;
        $doc->embedManyAddToSet = $colAddToSet;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        // No need to assert the value of the embedded document structure
        $this->assertBsonArray($doc->id, 'embedManyPush');
        $this->assertBsonArray($doc->id, 'embedManySet');
        $this->assertBsonArray($doc->id, 'embedManySetArray');
        $this->assertBsonArray($doc->id, 'embedManyAddToSet');

        // Check that the value is changed properly
        unset($colPush[1], $colSet[1], $colSetArray[1], $colAddToSet['1']);
        $doc                    = $this->dm->merge($doc);
        $doc->embedManyPush     = $colPush;
        $doc->embedManySet      = $colSet;
        $doc->embedManySetArray = $colSetArray;
        $doc->embedManyAddToSet = $colAddToSet;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArray($doc->id, 'embedManyPush');
        $this->assertBsonObject($doc->id, 'embedManySet');
        $this->assertBsonArray($doc->id, 'embedManySetArray');
        $this->assertBsonArray($doc->id, 'embedManyAddToSet');
    }

    public function testReferenceMany()
    {
        $colPush     = new ArrayCollection([
            new GH453ReferencedDocument(),
            new GH453ReferencedDocument(),
            new GH453ReferencedDocument(),
        ]);
        $colSet      = $colPush->map(static function ($v) {
            return clone $v;
        });
        $colSetArray = $colPush->map(static function ($v) {
            return clone $v;
        });
        $colAddToSet = $colPush->map(static function ($v) {
            return clone $v;
        });

        $dm = $this->dm;

        $colPush->forAll(static function ($k, $v) use ($dm) {
            $dm->persist($v);
            return true;
        });
        $colSet->forAll(static function ($k, $v) use ($dm) {
            $dm->persist($v);
            return true;
        });
        $colSetArray->forAll(static function ($k, $v) use ($dm) {
            $dm->persist($v);
            return true;
        });
        $colAddToSet->forAll(static function ($k, $v) use ($dm) {
            $dm->persist($v);
            return true;
        });

        $doc                        = new GH453Document();
        $doc->referenceManyPush     = $colPush;
        $doc->referenceManySet      = $colSet;
        $doc->referenceManySetArray = $colSetArray;
        $doc->referenceManyAddToSet = $colAddToSet;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        // No need to assert the value of the referenced document structure
        $this->assertBsonArray($doc->id, 'referenceManyPush');
        $this->assertBsonArray($doc->id, 'referenceManySet');
        $this->assertBsonArray($doc->id, 'referenceManySetArray');
        $this->assertBsonArray($doc->id, 'referenceManyAddToSet');

        // Check that the value is changed properly
        unset($colPush[1], $colSet[1], $colSetArray[1], $colAddToSet[1]);
        $doc->referenceManyPush     = $colPush;
        $doc->referenceManySet      = $colSet;
        $doc->referenceManySetArray = $colSetArray;
        $doc->referenceManyAddToSet = $colAddToSet;

        /* Merging must be done after re-assigning the collections, as the
         * referenced documents must be re-persisted through the merge cascade.
         */
        $doc = $this->dm->merge($doc);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertBsonArray($doc->id, 'referenceManyPush');
        $this->assertBsonObject($doc->id, 'referenceManySet');
        $this->assertBsonArray($doc->id, 'referenceManySetArray');
        $this->assertBsonArray($doc->id, 'referenceManyAddToSet');
    }

    private function assertBsonArray($documentId, $fieldName)
    {
        $this->assertBsonType(4, $documentId, $fieldName);
    }

    private function assertBsonObject($documentId, $fieldName)
    {
        $this->assertBsonType(3, $documentId, $fieldName);
    }

    private function assertBsonType($bsonType, $documentId, $fieldName)
    {
        $criteria = ['_id' => $documentId];

        if ($bsonType === 4) {
            // See: https://jira.mongodb.org/browse/SERVER-1475
            $criteria['$where'] = sprintf('Array.isArray(this.%s)', $fieldName);
        } else {
            $criteria[$fieldName] = ['$type' => $bsonType];
        }

        $this->assertNotNull($this->dm->getRepository(GH453Document::class)->findOneBy($criteria));
    }

    private function assertBsonArrayAndValue($expectedValue, $documentId, $fieldName)
    {
        $this->assertBsonTypeAndValue(4, $expectedValue, $documentId, $fieldName);
    }

    private function assertBsonObjectAndValue($expectedValue, $documentId, $fieldName)
    {
        $this->assertBsonTypeAndValue(3, $expectedValue, $documentId, $fieldName);
    }

    private function assertBsonTypeAndValue($bsonType, $expectedValue, $documentId, $fieldName)
    {
        if ($bsonType === 4) {
            $expectedValue = array_values((array) $expectedValue);
        } elseif ($bsonType === 3) {
            $expectedValue = (object) $expectedValue;
        }

        $criteria = [
            '_id' => $documentId,
            '$and' => [[$fieldName => $expectedValue]],
        ];

        if ($bsonType === 4) {
            // See: https://jira.mongodb.org/browse/SERVER-1475
            $criteria['$and'][] = ['$where' => sprintf('Array.isArray(this.%s)', $fieldName)];
        } else {
            $criteria['$and'][] = [$fieldName => ['$type' => $bsonType]];
        }

        $this->assertNotNull($this->dm->getRepository(GH453Document::class)->findOneBy($criteria));
    }
}

/** @ODM\Document */
class GH453Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="hash") */
    public $hash;

    /** @ODM\Field(type="collection") */
    public $colPush;

    /** @ODM\Field(type="collection") */
    public $colSet;

    /** @ODM\EmbedMany(strategy="pushAll")) */
    public $embedManyPush;

    /** @ODM\EmbedMany(strategy="set") */
    public $embedManySet;

    /** @ODM\EmbedMany(strategy="setArray") */
    public $embedManySetArray;

    /** @ODM\EmbedMany(strategy="addToSet") */
    public $embedManyAddToSet;

    /** @ODM\ReferenceMany(strategy="pushAll")) */
    public $referenceManyPush;

    /** @ODM\ReferenceMany(strategy="set") */
    public $referenceManySet;

    /** @ODM\ReferenceMany(strategy="setArray") */
    public $referenceManySetArray;

    /** @ODM\ReferenceMany(strategy="addToSet") */
    public $referenceManyAddToSet;
}

/** @ODM\EmbeddedDocument */
class GH453EmbeddedDocument
{
    /** @ODM\Id */
    public $id;
}

/** @ODM\Document */
class GH453ReferencedDocument
{
    /** @ODM\Id */
    public $id;
}
