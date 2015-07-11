<?php
/**
 * Add/fix some accessibility for LimeSurvey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2015 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 1.1.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class moreAccessibility extends PluginBase
{
    protected $storage = 'DbStorage';

    static protected $name = 'moreAccessibility';
    static protected $description = 'Update HTML for question for better labelling, and other accessibility waiting for CSS Aria';

    protected $settings = array(
        'updateAsterisk' => array(
            'type' => 'select',
            'options'=>array(
                0=> 'No',
                1=> 'Yes'
            ),
            'default'=>0,
            'label' => 'Update asterisk part to show real sentence.'
        ),
        'addAnswersFieldSet' => array(
            'type' => 'select',
            'options'=>array(
                0=> 'No',
                1=> 'Yes'
            ),
            'default'=>0,
            'label' => 'Add fieldset to answers art (list and array).'
        ),
    );

    public function __construct(PluginManager $manager, $id) {
        parent::__construct($manager, $id);
        $this->subscribe('beforeQuestionRender','questiontextLabel');
        $this->subscribe('beforeQuestionRender','mandatoryString');
        $this->subscribe('beforeQuestionRender','questionanswersFieldset');

    }

    public function questiontextLabel()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        // Label
        if(in_array($sType,array(
            "S","T","U",// Text question
            "N",// Numerical
            "I",// Language changer
            "!", // List dropdown
            )))
        {
            $oEvent->set('text',CHtml::label(
                $oEvent->get('text'),
                "answer".$oEvent->get('surveyId')."X".$oEvent->get('gid')."X".$oEvent->get('qid'),
                array('class'=>"fixlabel")
            ));
            $oEvent->set('answers',preg_replace('#<label(.*?)>(.*?)</label>#is', '', $oEvent->get('answers')));
        }
        // Date question type give format information, leave it ?
        // @todo : list radio with coment with dropdown enabled and list radio with dropdown too sometimes

    }
    public function questionanswersFieldset()
    {
        if(!$this->get('addAnswersFieldSet'))
            return;
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if(in_array($sType,array(
            "Q","K", // Multiple question : text/numeric multiple
            ";",":", // Array of input text/number
            "Y","G","5","L","O", // Single choice (radio)
            "F","H","A","B","E","C","1" // The arrays
            )))
        {
            // No legend .... need more HTML update : fieldset must include questiontext + answers.
            $oEvent->set('answers',CHtml::tag(
                'fieldset',
                array('form'=>'limesurvey','class'=>'fixfieldset'),
                $oEvent->get('answers')
                ));

        }
    }

    public function mandatoryString()
    {
        if(!$this->get('updateAsterisk'))
            return;
        $oEvent=$this->getEvent();
        if($oEvent->get('man_class') && strpos($oEvent->get('man_class'),'mandatory') !== false)
        {
            // Get the string from LimeSurvey core.
            $sMandatoryText=gT('This question is mandatory')."."; // Arg
            switch($oEvent->get('type'))
            {
                case 'M':
                case 'P':
                    $sMandatoryText.=gT('Please check at least one item.');
                    break;
                case 'A':
                case 'B':
                case 'C':
                case 'Q':
                case 'K':
                case 'E':
                case 'F':
                case 'J':
                case 'H':
                case ';':
                case '1':
                    $sMandatoryText.=gT('Please complete all parts').'.';
                    break;
                case ':':
                    $oAttribute=QuestionAttribute::model()->find("qid=:qid and attribute=:attribute",array(":qid"=>$oEvent->get('qid'),':attribute'=>'multiflexible_checkbox'));
                    if($oAttribute && $oAttribute->value)
                        $sMandatoryText.=gT('Please check at least one box per row').'.';
                    else
                        $sMandatoryText.=gT('Please complete all parts').'.';
                    break;
                case 'R':
                    $sMandatoryText.=gT('Please rank all items').'.';
                    break;
                default:
                    break;
                case '*':
                case 'X':
                    $sMandatoryText="";
                    break;
            }
            $oEvent->set('mandatory',$sMandatoryText);
        }
    }
}
