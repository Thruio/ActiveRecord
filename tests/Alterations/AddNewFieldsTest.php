<?php

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\Test\Models\AlteredTestModelHasExtraTextField;
use Thru\ActiveRecord\Test\Models\TestModel;

class AddNewFieldsTest extends BaseTest
{
    public function testAddATextField(){

        $text = $this->faker->sentence(rand(5,10);
        $extraText = $this->faker->sentence(rand(5,10);

        $testModel = new TestModel();
        $testModel->date_field = date("Y-m-d H:i:s");
        $testModel->integer_field = $this->faker->randomDigit;
        $testModel->text_field = $text;
        $testModel->save();

        // Reload this test model as an altered test model
        /** @var AlteredTestModelHasExtraTextField $alteredTestModel */
        $alteredTestModel = AlteredTestModelHasExtraTextField::search()
            ->where('test_model_id', $testModel->test_model_id)
            ->execOne();
        $alteredTestModel->extra_text_field = $extraText;
        $alteredTestModel->save();

        return [$testModel->test_model_id, $extraText];
    }

    /**
     * @depends testAddATextField
     */
    public function testVerifyAddATextField($list){
        list($test_model_id, $extraText) = $list;

        /** @var AlteredTestModelHasExtraTextField $alteredTestModel */
        $alteredTestModel = AlteredTestModelHasExtraTextField::search()
            ->where('test_model_id', $test_model_id)
            ->execOne();

        $this->assertEquals($extraText, $alteredTestModel->extra_text_field);
    }


}
