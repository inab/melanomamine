<?php

namespace Melanomamine\DocumentBundle\Twig;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Twig_Extension;
use Twig_Filter_Method;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UtilityExtension extends \Twig_Extension
{
    protected $doctrine;
    protected $generator;

    public function __construct(RegistryInterface $doctrine, UrlGeneratorInterface $generator)
    {
        $this->doctrine = $doctrine;
        $this->generator = $generator;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('highlight', array($this, 'highlight')),
            new \Twig_SimpleFilter('getScoreToShow', array($this, 'getScoreToShowFilter')),
            new \Twig_SimpleFilter('colorCodingScore', array($this, 'colorCodingScoreFilter')),
        );
    }


    public function highlight($source, $entityName){
        $offset=0; //To handle the offset of the text's starting point
        $title=$source["title"];
        $text=$source["text"];
        $titleText=$title . $text;

        /*
            For highlighting we create an array of dictionaries:  [{"start":4, "end": 20, "typeOf": "genes"},{"start":23, "end": 50, "typeOf": "diseases2"},{"start":56, "end": 80, "typeOf": "genes"},{"start":93, "end": 120, "typeOf": "mutatedProteins3"}...]
            We short it taking into account the "start" field
            We cut the string in parts and add span tags with colors. Then we concatenate the resulting strings and split the Title

        */

        $arrayDictionaries=[];
        $arrayIndexes=["chemicals", "diseases2", "genes", "mutatedProteins3", "snps", "species"];
        ld($source);
        foreach($arrayIndexes as $index){
            ld($index);
            if (array_key_exists($index, $source)){
                $typeOf=$index;
                $arrayData=$source[$index];//At $arrayData we have iteratively an array of chemicals, an array of diseases2... etc
                ld($arrayData);
                foreach($arrayData as $data){
                    ld($data);
                    $dictionaryTmp=[];
                    $dictionaryTmp["typeOf"]=$typeOf;
                    $dictionaryTmp["start"]=$data["startMention"];
                    $dictionaryTmp["end"]=$data["endMention"];
                    $dictionaryTmp["mention"]=$data["mention"];
                    array_push($arrayDictionaries, $dictionaryTmp);
                }
            }
        }
        usort($arrayDictionaries,function($item1, $item2){
                if ($item1['start'] == $item2['start']) return 0;
                return ($item1['start'] > $item2['start']) ? 1 : -1;
            }
        );
        ld($arrayDictionaries);
        //Now we generate the new arrayString with the added highlights:
        //We have to iterate over the arrayDictionaries and mark $titleTextDefinitive
        foreach($arrayDictionaries as $dictionary){
            $start=$dictionary["start"];
            $end=$dictionary["end"];
            $typeOf=$dictionary["typeOf"];
            //The string_to_insert will be different depending on the typeOf. Therefore:
            switch ($typeOf){
                case "chemicals":
                    $str_to_insert="<span class='chemical_highlight'>";
                    $addToOffset=33;
                    break;
                case "diseases2":
                    $str_to_insert="<span class='diseases_highlight'>";
                    $addToOffset=33;
                    break;
                case "genes":
                    $str_to_insert="<span class='genes_highlight'>";
                    $addToOffset=30;
                    break;
                case "mutatedProteins3":
                    $str_to_insert="<span class='mutatedProteins_highlight'>";
                    $addToOffset=40;
                    break;
                case "snps":
                    $str_to_insert="<span class='snps_highlight'>";
                    $addToOffset=29;
                    break;
                case "species":
                    $str_to_insert="<span class='species_highlight'>";
                    $addToOffset=32;
                    break;
            }
            //Change at start position
            $titleText = substr_replace($titleText, $str_to_insert, $start+$offset-1, 0);
            $offset=$offset+$addToOffset;
            //Change at end position
            $str_to_insert="</span>";
            $titleText = substr_replace($titleText, $str_to_insert, $end+$offset-1, 0);
            $offset=$offset+7;

        }
        ld($titleText);
        return ($titleText);//Remember to return an array with [0]=title_highlighted, [1]=text_highlighted
    }

    public function getScoreToShowFilter($orderBy){
        switch ($orderBy) {
            case $orderBy == "score":
                $orderBy ="score";
                break;
            case $orderBy == "hepval":
                $orderBy ="SVM";
                break;
            case $orderBy == "svmConfidence":
                $orderBy ="Conf.";
                break;
            case $orderBy == "patternCount":
                $orderBy ="Pattern";
                break;
            case $orderBy == "hepTermVarScore":
                $orderBy ="Term";
                break;
            case $orderBy == "ruleScore":
                $orderBy ="Rule";
                break;
        }
        return $orderBy;
    }

    public function colorCodingScoreFilter($score)
    {
        if ($score==null){
            $score="-";
            return $score;
        }
        $score=(float)$score;
        switch ($score) {
            case (int)$score === 0:
                    $score ="<mark class=''>$score</mark>";
                    break;
            case $score>5:
                    $score ="<mark class='score-green-5'>$score</mark>";
                    break;
            case $score>4:
                    $score ="<mark class='score-green-4'>$score</mark>";
                    break;
            case $score>3:
                    $score ="<mark class='score-green-3'>$score</mark>";
                    break;
            case $score>2:
                    $score ="<mark class='score-green-2'>$score</mark>";
                    break;
            case $score>1:
                    $score ="<mark class='score-green-1'>$score</mark>";
                    break;
            case $score>0:
                    $score ="<mark class='score-green-0'>$score</mark>";
                    break;
            case $score < -5:
                    $score ="<mark class='score-red-5'>$score</mark>";
                    break;
            case $score < -4:
                    $score ="<mark class='score-red-4'>$score</mark>";
                    break;
            case $score < -3:
                    $score ="<mark class='score-red-3'>$score</mark>";
                    break;
            case $score < -2:
                    $score ="<mark class='score-red-2'>$score</mark>";
                    break;
            case $score < -1:
                    $score ="<mark class='score-red-1'>$score</mark>";
                    break;
            case $score < 0:
                    $score ="<mark class='score-red-0'>$score</mark>";
                    break;


        }
        return ($score);
    }

    public function getName()
    {
        return 'utility_extension';
    }
}


?>