<?php
/**
 * Add/fix some accessibility for LimeSurvey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2015 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 1.3.1
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
        'infoAlwaysActivate'=>array(
            'type' => 'info',
            'content' => '<p class="alert">Some system can not be deactivated :</p><ul><li> use question text for labelling the single question type,</li><li> fix checkbox with other label and radio with other label.</li></ul>',
        ),
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
                0=> 'No, use only aria',
                1=> 'Yes'
            ),
            'default'=>0,
            'label' => 'Add fieldset to answers art (list and array), attention : this can break your template.',
            'help' => 'Is set to no : Radio list and checkbox list use aria=group and labelled-by'
        ),
    );

    /**
    * Add function to be used in beforeQuestionRender event
    */
    public function init()
    {
        $this->subscribe('beforeQuestionRender','questiontextLabel');
        $this->subscribe('beforeQuestionRender','mandatoryString');
        $this->subscribe('beforeQuestionRender','questionanswersListGrouping');
        $this->subscribe('beforeQuestionRender','checkboxLabelOther');
        $this->subscribe('beforeQuestionRender','radioLabelOther');
    }

    /**
    * Use the question text as label for single question , use aria-labelledby for help and tips
    */
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
            $this->registerCssJs();
            $sAnswerId="answer{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
            $oEvent->set('text',CHtml::label(
                $oEvent->get('text'),
                $sAnswerId,
                array('class'=>"moreaccessibility-fixlabel",'id'=>"label-{$sAnswerId}")
            ));
            // find the labelled-by
            $aLabelledBy=array(
                "label-{$sAnswerId}",
            );
            // What is the good order ?
            if($this->get('updateAsterisk') && $oEvent->get('man_class'))
            {
                $aLabelledBy[]="mandatory-{$oEvent->get('qid')}";
            }
            if($oEvent->get('questionhelp'))
            {
                $aLabelledBy[]="questionhelp-{$sAnswerId}";
                $oEvent->set('questionhelp',CHtml::tag('div',array('id'=>"questionhelp-{$sAnswerId}"),$oEvent->get('questionhelp')));
            }
            if($oEvent->get('help'))
            {
                $aLabelledBy[]="help-{$sAnswerId}";
                $oEvent->set('help',CHtml::tag('div',array('id'=>"help-{$sAnswerId}"),$oEvent->get('help')));
            }
            if(strip_tags($oEvent->get('valid_message'))!="")
            {
                $aLabelledBy[]="vmsg_{$oEvent->get('qid')}";
            }
            else
            {
                $oEvent->set('valid_message','');
            }
            Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
            Yii::import('archon810.SmartDOMDocument');
            $dom = new \archon810\SmartDOMDocument();
            @$dom->loadHTML($oEvent->get('answers'));
            foreach($dom->getElementsByTagName('label') as $label)
              $label->parentNode->removeChild($label);
            $input=$dom->getElementById($sAnswerId);
            $input->setAttribute("aria-labelledby",implode(" ",$aLabelledBy));
            $newHtml = $dom->saveHTMLExact();

            $oEvent->set('answers',$newHtml);
        }
        // Date question type give format information, leave it ?
        // @todo : list radio with coment with dropdown enabled and list radio with dropdown too sometimes

    }


    public function questionanswersListGrouping()
    {
        if($this->get('addAnswersFieldSet'))
            $this->addAnswersFieldSet();
        else
            $this->ariaAnswersGroup();
    }
    /**
    * Add fieldset to multiple question or multiple answers, move quetsion text + help + tip to label
    */
    public function addAnswersFieldSet()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if(in_array($sType,array(
            "M","P","Q","K", // Multiple question : text/numeric multiple
            ";",":", // Array of input text/number
            "Y","G","5","L","O", // Single choice (radio)
            "F","H","A","B","E","C","1" // The arrays
            )))
        {
            $this->registerCssJs();
            // No legend .... need more HTML update : fieldset must include questiontext + answers.
            $sLegend=CHtml::tag("div",array("class"=>'question-moved'),$oEvent->get('text'));
            $oEvent->set('text','');
            $sLegend.=CHtml::tag("div",array("class"=>'help-moved'),$oEvent->get('help'));
            $oEvent->set('help','');
            $sLegend.=CHtml::tag("div",array("class"=>'man_message-moved'),$oEvent->get('man_message'));
            $oEvent->set('man_message','');
            $sLegend.=CHtml::tag("div",array("class"=>'valid_message-moved'),$oEvent->get('valid_message'));
            $oEvent->set('valid_message','');
            $sLegend.=CHtml::tag("div",array("class"=>'file_valid_message-moved'),$oEvent->get('file_valid_message'));
            $oEvent->set('file_valid_message','');
            $oEvent->set('answers',CHtml::tag(
                'fieldset',
                array('form'=>'limesurvey','class'=>'fixfieldset'),
                CHtml::tag('legend',array(),$sLegend).$oEvent->get('answers')
                ));
        }
    }

    /**
    * Use aria to group answers part
    */
    public function ariaAnswersGroup()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if(in_array($sType,array(
            "M","P","Q","K", // Multiple question : text/numeric multiple
            "Y","G","5","L","O", // Single choice (radio)
            )))
        {
            $this->registerCssJs();
            $oEvent->set('text',CHtml::tag("div",array('id'=>"description-{$oEvent->get('qid')}",'class'=>'moreaccessibility-fixlabel'),$oEvent->get('text')));
            $aDescribedBy=array(
                "description-{$oEvent->get('qid')}",
            );
            // What is the good order ?
            if($this->get('updateAsterisk') && $oEvent->get('man_class'))
            {
                $aDescribedBy[]="mandatory-{$oEvent->get('qid')}";
            }
            if($oEvent->get('questionhelp'))
            {
                $aDescribedBy[]="questionhelp-{$oEvent->get('qid')}";
                $oEvent->set('questionhelp',CHtml::tag('div',array('id'=>"questionhelp-{$oEvent->get('qid')}"),$oEvent->get('questionhelp')));
            }
            if($oEvent->get('help'))
            {
                $aDescribedBy[]="help-{$oEvent->get('qid')}";
                $oEvent->set('help',CHtml::tag('div',array('id'=>"help-{$oEvent->get('qid')}"),$oEvent->get('help')));
            }
            if(strip_tags($oEvent->get('valid_message'))!="")
            {
                $aDescribedBy[]="vmsg_{$oEvent->get('qid')}";
            }
            else
            {
                $oEvent->set('valid_message','');
            }
            switch ($sType)
            {
              case "Y":
              case "G":
              case "5":
              case "L":
              case "O":
                $sRole='radiogroup';
                break;
              default:
                $sRole='group';
            }
            Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
            Yii::import('archon810.SmartDOMDocument');
            $dom = new \archon810\SmartDOMDocument();
            $dom->loadHTML($oEvent->get('answers'));
            foreach ($dom->getElementsByTagName('ul') as $elList)
            {
                $elList->setAttribute('role',$sRole);
                $elList->setAttribute('aria-labelledby',implode(" ",$aDescribedBy));
            }
            $newHtml = $dom->saveHTMLExact();
            $oEvent->set('answers',$newHtml);
        }
    }

    /**
    * Update the mandatory * to a clean string, according to question type
    */
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
            $oEvent->set('mandatory',CHtml::tag('span',array('id'=>"mandatory-{$oEvent->get('qid')}"),$sMandatoryText));
        }
    }

    /*
     * On checkbox list, when the "other" option is activated, hidden the checkbox and leave only label for other text
     */
    public function checkboxLabelOther()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if(in_array($sType,array(
            "M", // Input Checkbox List
            )))
        {
          if (strpos( $oEvent->get('answers'), "othercbox") > 0) // Only do it if we have other : can be done with Question::model()->find
          {
              $sAnswerOtherTextId="answer{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}other";
              $sAnswerOtherCboxId="answer{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}othercbox";

              Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
              Yii::import('archon810.SmartDOMDocument');
              $dom = new \archon810\SmartDOMDocument();
              $dom->loadHTML($oEvent->get('answers'));
              // Update the checkbox
              $cbox=$dom->getElementById($sAnswerOtherCboxId);
              $cbox->setAttribute("aria-hidden","true");
              $cbox->setAttribute("tabindex","-1");
              $cbox->setAttribute("readonly","readonly");// disabled broken by survey-runtime
              // remove exiting script
              while (($r = $dom->getElementsByTagName("script")) && $r->length) {
                  $r->item(0)->parentNode->removeChild($r->item(0));
              }
              $newHtml = $dom->saveHTMLExact();
              $oEvent->set('answers',$newHtml);
              // Add own script
              $sMoreAccessibilityCheckboxLabelOtherScript="$(document).on('keyup focusout','#{$sAnswerOtherTextId}',function(){\n"
                      . "  if ($.trim($(this).val()).length>0) { $('#{$sAnswerOtherCboxId}').prop('checked',true); } else { $('#{$sAnswerOtherCboxId}').prop('checked',false); }\n"
                      ." $('#java{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}other').val($(this).val());LEMflagMandOther('{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}other',$('#{$sAnswerOtherCboxId}').is(':checked')); checkconditions($(this).val(), this.name, this.type);\n"
                      . "});\n"
                      . "$(document).on('click','#{$sAnswerOtherCboxId}',function(){\n"
                      . "  $('#{$sAnswerOtherTextId}').focus();\n"
                      . "})\n";
              App()->getClientScript()->registerScript('sMoreAccessibilityCheckboxLabelOtherScript',$sMoreAccessibilityCheckboxLabelOtherScript,CClientScript::POS_HEAD);
          }
        }
    }
    /*
    * Fix labelling on other in radio list : use aria-labelledby for text input
    * @link https://www.limesurvey.org/en/forum/plugins/100988-moreaccessibility#127710 Thanks to Alexandre Landry <forXcodeur@gmail.com>
    */
    public function radioLabelOther()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if(in_array($sType,array(
            "L", // Radio Button List
            )))
        {
          if (strpos( $oEvent->get('answers'), 'id="SOTH') > 0) // Only do it if we have other : can be done with Question::model()->find
          {
              $sAnswerOtherTextId="answer{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}othertext";
              $sAnswerOtherRadioId="SOTH{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
              Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
              Yii::import('archon810.SmartDOMDocument');
              $dom = new \archon810\SmartDOMDocument();
              $dom->loadHTML($oEvent->get('answers'));
              $elOtherText=$dom->getElementById($sAnswerOtherTextId);
              $elOtherText->setAttribute("aria-labelledby","label-{$sAnswerOtherRadioId}");
              $elOtherText->removeAttribute ('title');
              foreach ($dom->getElementsByTagName('label') as $elLabel)
              {
                  if($elLabel->getAttribute("for")==$sAnswerOtherRadioId)
                      $elLabel->setAttribute('id',"label-{$sAnswerOtherRadioId}");
                  if($elLabel->getAttribute("for")==$sAnswerOtherTextId)
                  {
                      $elLabel->parentNode->replaceChild($elOtherText, $elLabel);
                  }
              }
              $newHtml = $dom->saveHTMLExact();
              $oEvent->set('answers',$newHtml);
          }
        }
    }

    /**
    * Register needed css and js
    */
    private function registerCssJs()
    {
        $assetUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/assets');
        Yii::app()->clientScript->registerCssFile($assetUrl . '/css/moreaccessibility.css');
    }
}
