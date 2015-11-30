<?php

namespace Melanomamine\DocumentBundle\Twig\Extension;

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
            'retrieveHighlighted' => new \Twig_Filter_Method($this, 'retrieveHighlighted'),
        );
    }



    public function filterTitleText($source, $filter){
        $message="Inside filterTitleText";
        //ld($filter);

        /*
            For highlighting we create an array of dictionaries:  [{"start":4, "end": 20, "typeOf": "genes"},{"start":23, "end": 50, "typeOf": "diseases2"},{"start":56, "end": 80, "typeOf": "genes"},{"start":93, "end": 120, "typeOf": "mutatedProteins3"}...]
            We short it taking into account the "start" field
            We cut the string in parts and add span tags with colors. Then we concatenate the resulting strings and split the Title

        */
        $usedPositions=[];//To track positions already highlighted and avoid double-highlight
        if($filter=="title"){
            //Filter source to get only source involved in title positions
            $titleText=$source["title"];
            $titleLength=strlen($titleText);
            $arrayDictionaries=[];
            $arrayIndexes=["chemicals", "diseases2", "genes", "mutatedProteins3", "snps", "species"];
            //ld($source);
            foreach($arrayIndexes as $index){
                //ld($index);
                if (array_key_exists($index, $source)){
                    $typeOf=$index;
                    $arrayData=$source[$index];//At $arrayData we have iteratively an array of chemicals, an array of diseases2... etc
                    //ld($arrayData);
                    foreach($arrayData as $data){
                        //ld($data);
                        $dictionaryTmp=[];
                        $dictionaryTmp["typeOf"]=$typeOf;
                        if($typeOf == "mutatedProteins3"){
                            $start=$data["startMutation"];
                            $end=$data["endMutation"];
                            $dictionaryTmp["start"]=$start;
                            $dictionaryTmp["end"]=$end;
                        }elseif($typeOf=="diseases2"){
                            //Addded to correct offset error for starting position for diseases in database
                            $start=$data["startMention"];
                            $end=$data["endMention"];
                            $dictionaryTmp["start"]=$start-1;//Addded to correct offset error for starting position for diseases in database
                            $dictionaryTmp["end"]=$end;
                        }else{
                            $start=$data["startMention"];
                            $end=$data["endMention"];
                            $dictionaryTmp["start"]=$start;
                            $dictionaryTmp["end"]=$end;
                        }

                        //Now we check if those positions are already highlighted or not.
                        //$message="start: $start - end: $end";
                        //ld($message);
                        //ld($usedPositions);
                        if( !in_array($start, $usedPositions) and !in_array($end, $usedPositions)){
                            //$message="start o end no se encuentran en el array usedPositions. Añadimos todo el rango";
                            //ld($message);
                            $dictionaryTmp["mention"]=$data["mention"];
                            $tmpUsedPositions=range($start,$end);
                            $usedPositions = array_merge($usedPositions, $tmpUsedPositions);
                            //ld($usedPositions);
                            if($start <= $titleLength){
                                array_push($arrayDictionaries, $dictionaryTmp);
                                //ld($arrayDictionaries);
                            }
                        }else{
                            $message="start o end se encuentran en el array usedPositions";
                            //ld($message);
                        }

                    }
                }
            }
            usort($arrayDictionaries,function($item1, $item2){
                    if ($item1['start'] == $item2['start']) return 0;
                    return ($item1['start'] > $item2['start']) ? 1 : -1;
                }
            );
        }elseif($filter=="text"){
            //Filter source to get only source involved in title positions
            $titleText=$source["text"];

            $offset=strlen($source["title"]);
            //ld($offset);
            $arrayDictionaries=[];
            $arrayIndexes=["chemicals", "diseases2", "genes", "mutatedProteins3", "snps", "species"];
            //ld($source);
            foreach($arrayIndexes as $index){
                //ld($index);
                if (array_key_exists($index, $source)){
                    $typeOf=$index;
                    $arrayData=$source[$index];//At $arrayData we have iteratively an array of chemicals, an array of diseases2... etc
                    //ld($arrayData);
                    foreach($arrayData as $data){
                        //ld($data);
                        $dictionaryTmp=[];
                        $dictionaryTmp["typeOf"]=$typeOf;
                        if($typeOf == "mutatedProteins3"){
                            $start=$data["startMutation"];
                            $end=$data["endMutation"];
                            $dictionaryTmp["start"]=$start;
                            $dictionaryTmp["end"]=$end;
                        }/*elseif($typeOf=="diseases2"){
                            //Addded to correct offset error for starting position for diseases in database
                            $start=$data["startMention"];
                            $end=$data["endMention"];
                            $dictionaryTmp["start"]=$start-1;//Addded to correct offset error for starting position for diseases in database
                            $dictionaryTmp["end"]=$end;
                        }*/else{
                            $start=$data["startMention"];
                            $end=$data["endMention"];
                            $dictionaryTmp["start"]=$start;
                            $dictionaryTmp["end"]=$end;
                        }

                        //Now we check if those positions are already highlighted or not.
                        //$message="start: $start - end: $end";
                        //ld($message);
                        //ld($usedPositions);
                        if( !in_array($start, $usedPositions) and !in_array($end, $usedPositions)){
                            //$message="start o end no se encuentran en el array usedPositions. Añadimos todo el rango";
                            //ld($message);
                            $dictionaryTmp["mention"]=$data["mention"];
                            $tmpUsedPositions=range($start,$end);
                            $usedPositions = array_merge($usedPositions, $tmpUsedPositions);
                            //ld($usedPositions);
                            if($start >= $offset){
                                array_push($arrayDictionaries, $dictionaryTmp);
                            }
                        }else{
                            $message="start o end se encuentran en el array usedPositions";
                            //ld($message);
                        }
                    }
                }
            }
            usort($arrayDictionaries,function($item1, $item2){
                    if ($item1['start'] == $item2['start']) return 0;
                    return ($item1['start'] > $item2['start']) ? 1 : -1;
                }
            );
        }
        return ($arrayDictionaries);
    }

    public function retrieveHighlighted($titleOrText, $source, $entityName, $startOffset, $filter){
        $message="Inside retrieveHighlighted service";
        $offset=0; //To handle the offset of the text's starting point
        //$startOffset needs to be substracted to compensate the title positions
        //ld($source);
        //ld($titleOrText);
        if($filter=="title"){
            $source=$this->filterTitleText($source, "title");
            //ld($startOffset);
            //ld($source);
        }elseif($filter=="text"){
            $source=$this->filterTitleText($source, "text");
            //ld($titleOrText);
            //ld($startOffset);
            //ld($source);
        }
        $arrayDictionaries=$source;
        //ld($arrayDictionaries);
        //Now we generate the new arrayString with the added highlights:
        //We have to iterate over the arrayDictionaries and mark $titleTextDefinitive
        $counter=1;
        foreach($arrayDictionaries as $dictionary){
            $start=$dictionary["start"];
            //ld($start);
            $end=$dictionary["end"];
            $typeOf=$dictionary["typeOf"];
            //The string_to_insert will be different depending on the typeOf. Therefore:
            switch ($typeOf){
                case "chemicals":
                    $str_to_insert="<span class='chemicals_highlight'>";
                    $addToOffset=34;
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
            //ld($str_to_insert);
            if($filter=="title"){
                //Change at start position
                $position=$start-$startOffset+$offset;
                $titleOrText = substr_replace($titleOrText, $str_to_insert, $position, 0);
                $offset=$offset+$addToOffset;

                //Change at end position
                $str_to_insert="</span>";
                $position=$end-$startOffset +$offset;
                $titleOrText = substr_replace($titleOrText, $str_to_insert, $position, 0);
                $offset=$offset+7;
            }elseif($filter=="text"){
                //Change at start position
                $position=$start-$startOffset+$offset;
                $titleOrText = substr_replace($titleOrText, $str_to_insert, $position, 0);
                $offset=$offset+$addToOffset;

                //Change at end position
                $str_to_insert="</span>";
                $position=$end-$startOffset +$offset;
                $titleOrText = substr_replace($titleOrText, $str_to_insert, $position, 0);
                $offset=$offset+7;
            }
            //ld($titleOrText);
        }
        //ld($titleOrText);
        //Underline entityName
        $titleOrText=str_replace($entityName, "<span class='underline'>".$entityName."</span>", $titleOrText);
        return ($titleOrText);
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