<?php
/**
 * Description
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2017 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 0.0.0
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


function getAccessibleFooter() {
    return "\n</body>\n</html>";
}
function getAccessibleHeader() {
    global $surveyid ;
    
    Yii::app()->loadHelper('surveytranslator');
    $languagecode=Yii::app()->getLanguage();
    $dir=getLanguageRTL($languagecode) ? "rtl":"ltr";
    $header = "<!DOCTYPE html>\n";
    $header.= "<html lang=\"{$languagecode}\" dir=\"{$dir}\">\n";
    $header.= "<head>\n";
    return $header;
    //~ if (Yii::app()->session['survey_'.$surveyid]['s_lang'] ) {
        //~ $languagecode =  Yii::app()->session['survey_'.$surveyid]['s_lang'];
    //~ } elseif (isset($surveyid) && $surveyid  && Survey::model()->findByPk($surveyid)) {
        //~ $languagecode=Survey::model()->findByPk($surveyid)->language;
    //~ }
    //~ else
    //~ {
        //~ $languagecode = Yii::app()->getConfig('defaultlang');
    //~ }
}
