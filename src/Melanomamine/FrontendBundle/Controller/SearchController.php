<?php

namespace Melanomamine\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use \Elastica9205\Request;



class SearchController extends Controller
{

    public function mmmr($array, $output = 'mean'){
        //Function to get mean(default), median, mode and range of $array input
        if(!is_array($array)){
            return FALSE;
        }else{
            switch($output){
                case 'mean':
                    $count = count($array);
                    $sum = array_sum($array);
                    $total = $sum / $count;
                break;
                case 'median':
                    rsort($array);
                    $middle = round(count($array) / 2);
                    $total = $array[$middle-1];
                break;
                case 'range':
                    sort($array);
                    $sml = $array[0];
                    rsort($array);
                    $lrg = $array[0];
                    $total = $lrg - $sml;
                break;
            }
            return $total;
        }
    }

    public function getMmmrScore($resultSetDocuments, $orderBy, $operation = 'mean'){
        //Function that receives a resultSetDocuments and extracts the mean value of the score

        $arrayResults=$resultSetDocuments->getResults();
        if(count($arrayResults)==0){
            return 0;
        }
        $arrayInput=array();
        foreach($arrayResults as $result){
            if($orderBy=="score"){
                $orderBy="score";
            }
            $arrayData=$result->getSource();
            $data=$arrayData[$orderBy];
            if($data==null){
                $data=0;
            }
            $arrayInput[]=$data;
        }
        if(count($arrayInput)!=0){
            $output=$this->mmmr($arrayInput, $operation);
        }
        else{
            $output=0;
        }
        return $output;
    }

    public function filterTitleText($source, $filter){
        $message="Inside filterTitleText";
        //ld($source);

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

    public function getHighlighted($source, $titleOrText, $entityName, $startOffset, $filter){
        $offset=0; //To handle the offset of the text's starting point
        //$startOffset needs to be substracted to compensate the title positions
        //ld($source);
        //ld($titleOrText);

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

            if($filter=="title"){
                //Change at start position
                $position=$start-$startOffset+$offset-1;
                $titleOrText = substr_replace($titleOrText, $str_to_insert, $position, 0);
                $offset=$offset+$addToOffset;

                //Change at end position
                $str_to_insert="</span>";
                $position=$end-$startOffset +$offset;
                $titleOrText = substr_replace($titleOrText, $str_to_insert, $position, 0);
                $offset=$offset+7;
            }elseif($filter=="text"){
                //Change at start position
                $position=$start-$startOffset+$offset-1;
                $titleOrText = substr_replace($titleOrText, $str_to_insert, $position, 0);
                $offset=$offset+$addToOffset;

                //Change at end position
                $str_to_insert="</span>";
                $position=$end-$startOffset +$offset-1;
                $titleOrText = substr_replace($titleOrText, $str_to_insert, $position, 0);
                $offset=$offset+7;
            }

        }
        //ld($titleOrText);
        return ($titleOrText);
    }

    public function getStringHtmlResults($arrayResultsAbs, $entityName){
        $message="inside getStringHtmlResults";
        $stringHtml="";
        //ld($arrayResultsAbs);
        //ld($entityName);
        foreach($arrayResultsAbs as $result){
            //ld($result);
            $stringHtml.="<tr class='document'>";

            $source=$result->getSource();
            $pmid = $source['pmid'];
            $title = $source['title'];
            $text = $source['text'];
            $titleText = $title . $text;
            //ld($titleText);

            //Start Highlight
            $source=$result->getSource();
            //First of all we filter $source for title highlight endeavour
            $sourceTitle=$this->filterTitleText($source, "title");
            //ld($sourceTitle);
            //Then we filter $source for text highlight endeavour
            $sourceText=$this->filterTitleText($source, "text");
            //ld($sourceText);

            $offset=0;

            $titleHighlighted=$this->getHighlighted($sourceTitle, $title, $entityName, $offset, "title");
            //ld($titleHighlighted);

            $offset=strlen($title);

            $textHighlighted=$this->getHighlighted($sourceText, $text, $entityName, $offset, "text");
            //ld($textHighlighted);

            $score = $result->getSource()['melanoma_score_new'];
            $score = number_format((float)$score, 3, '.', '');
            $link = "http://www.ncbi.nlm.nih.gov/pubmed/" . $pmid;
            $imageRoute = 'http://melanomamine.bioinfo.cnio.es/images/pubmed.png';
            $stringHtml.="<td class='center'>";
            $stringHtml.="<a href='$link' target='_blank' title='PMID: $pmid'><img src='$imageRoute' class='outlinkLogo'/></a>";
            $stringHtml.="</td>";
            $stringHtml.="<td class='center'>$score</td>";
            $stringHtml.="<td><strong>$titleHighlighted</strong></td>";
            $stringHtml.="<td>$textHighlighted</td>";
            $stringHtml.="</tr>";
        }
        //ld($stringHtml);
        return ($stringHtml);
    }

    public function searchKeywordsAction($whatToSearch,$entityName)
    {

        $entityType="keywords";
        $message="inside searchKeywordAction";


        $elasticaQuery  = new \Elastica\Query();
        $elasticaQuery->setSize(1000);
        $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

        $queryString  = new \Elastica\Query\QueryString();
        //'And' or 'Or' default : 'Or'
        $queryString->setDefaultOperator('AND');
        $queryString->setQuery($entityName);

        if($whatToSearch=="freeText"){


            $elasticaQuery->setQuery($queryString);

        }elseif($whatToSearch=="withGenesProtein"){

            $field = "genes";
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withProteinMutations"){

            $field = "mutatedProteins3";
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withSNPs"){

            $field = "snps";
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withDNAmutations"){

            $field = "mutations";
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withChemicals"){

            $field = "chemicals";
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withDiseases"){

            $field = "diseases2";
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }



        $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();

        $paginator = $this->get('ideup.simple_paginator');
        $arrayResultsAbs = $paginator
            //->setMaxPagerItems($this->container->getParameter('etoxMicrome.number_of_pages'), 'abstracts')
            ->setMaxPagerItems(15, 'abstracts')
            //->setItemsPerPage($this->container->getParameter('etoxMicrome.evidences_per_page'), 'abstracts')
            ->setItemsPerPage(10, 'abstracts')
            ->paginate($arrayAbstracts,'abstracts')
            ->getResult()
        ;
    ############### Uncomment when a SCORE has been added to the elasticsearch entries
        //$meanScore=$this->getMmmrScore($data, 'score', 'mean');
        //$medianScore=$this->getMmmrScore($data, $orderBy, 'median');
        //$rangeScore=$this->getMmmrScore($data, $orderBy, 'range');
        //$finderDoc=false;
        ############### Comment when a SCORE has been added to the elasticsearch entries
        $meanScore=0;
        $medianScore=0;
        $rangeScore=0;

        $resultSetDocuments = array();
        $arrayResultsDoc = array();

        $stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

        return $this->render('MelanomamineFrontendBundle:Search:results.html.twig', array(
            'entityType' => $entityType,
            'whatToSearch' => $whatToSearch,
            'entityName' => $entityName,
            'arrayResultsAbs' => $arrayResultsAbs,
            'arrayResultsDoc' => $arrayResultsDoc,
            'resultSetAbstracts' => $data,
            'resultSetDocuments' => $resultSetDocuments,
            'entityName' => $entityName,
            'orderBy' => "score",
            'hitsShowed' => $totalHits,
            'meanScore' => $meanScore,
            'medianScore' => $medianScore,
            'rangeScore' => $rangeScore,
            'totalTime' => $totalTime,
            'stringHtml' => $stringHtml,
        ));
    }



    public function searchGenesAction($whatToSearch, $entityName, $human)
    {
        $entityType="genes";
        $message="inside searchGenesAction";

        if ($whatToSearch=="geneProteinName"){
            $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

            $elasticaQuery = new \Elastica\Query();
            $elasticaQuery->setSize(1000);
            $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

            //BoolQuery to load 2 queries.
            $queryBool = new \Elastica\Query\BoolQuery();

            //First query to search inside nested genes.mention
            $searchNested = new \Elastica\Query\QueryString();
            $searchNested->setParam('query', $entityName);
            $searchNested->setParam('fields', array('genes.mention'));

            $nestedQuery = new \Elastica\Query\Nested();
            $nestedQuery->setQuery($searchNested);
            $nestedQuery->setPath('genes');

            $queryBool->addMust($nestedQuery);

            //Second query to search inside nested genes.ontologyId to see if it's a human gene
            $searchNested2 = new \Elastica\Query\QueryString();
            $searchNested2->setParam('query', 9606);
            $searchNested2->setParam('fields', array('genes.ontologyId'));

            $nestedQuery2 = new \Elastica\Query\Nested();
            $nestedQuery2->setQuery($searchNested2);
            $nestedQuery2->setPath('genes');

            if ($human=="human"){
                $queryBool->addMust($nestedQuery2);
            }elseif($human=="not_human"){
                $queryBool->addMustNot($nestedQuery2);
            }

            $elasticaQuery->setQuery($queryBool);


            $data = $finder->search($elasticaQuery);
            $totalHits = $data->getTotalHits();
            $totalTime = $data->getTotalTime();
            $arrayAbstracts=$data->getResults();

        }
        elseif($whatToSearch=="ncbiGenId"){
            $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

            $elasticaQuery = new \Elastica\Query();
            $elasticaQuery->setSize(1000);
            $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

            //BoolQuery to load 2 queries.
            $queryBool = new \Elastica\Query\BoolQuery();

            //First query to search inside nested genes.mention
            $searchNested = new \Elastica\Query\QueryString();
            $searchNested->setParam('query', $entityName);
            $searchNested->setParam('fields', array('mutatedProteins3.ncbiGenId'));

            $nestedQuery = new \Elastica\Query\Nested();
            $nestedQuery->setQuery($searchNested);
            $nestedQuery->setPath('mutatedProteins3');

            $queryBool->addMust($nestedQuery);

            //Second query to search inside nested genes.ontologyId to see if it's a human gene
            $searchNested2 = new \Elastica\Query\QueryString();
            $searchNested2->setParam('query', 9606);
            $searchNested2->setParam('fields', array('mutatedProteins3.ncbiTaxId'));

            $nestedQuery2 = new \Elastica\Query\Nested();
            $nestedQuery2->setQuery($searchNested2);
            $nestedQuery2->setPath('mutatedProteins3');

            if ($human=="human"){
                $queryBool->addMust($nestedQuery2);
            }elseif($human=="not_human"){
                $queryBool->addMustNot($nestedQuery2);
            }

            $elasticaQuery->setQuery($queryBool);


            $data = $finder->search($elasticaQuery);
            $totalHits = $data->getTotalHits();
            $totalTime = $data->getTotalTime();
            $arrayAbstracts=$data->getResults();

        }

        $paginator = $this->get('ideup.simple_paginator');
        $arrayResultsAbs = $paginator
            //->setMaxPagerItems($this->container->getParameter('etoxMicrome.number_of_pages'), 'abstracts')
            ->setMaxPagerItems(15, 'abstracts')
            //->setItemsPerPage($this->container->getParameter('etoxMicrome.evidences_per_page'), 'abstracts')
            ->setItemsPerPage(10, 'abstracts')
            ->paginate($arrayAbstracts,'abstracts')
            ->getResult()
        ;
    ############### Uncomment when a SCORE has been added to the elasticsearch entries
        //$meanScore=$this->getMmmrScore($data, 'score', 'mean');
        //$medianScore=$this->getMmmrScore($data, $orderBy, 'median');
        //$rangeScore=$this->getMmmrScore($data, $orderBy, 'range');
        //$finderDoc=false;
        ############### Comment when a SCORE has been added to the elasticsearch entries
        $meanScore=0;
        $medianScore=0;
        $rangeScore=0;

        $resultSetDocuments = array();
        $arrayResultsDoc = array();

        $stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

        return $this->render('MelanomamineFrontendBundle:Search:results.html.twig', array(
            'entityType' => $entityType,
            'whatToSearch' => $whatToSearch,
            'entityName' => $entityName,
            'arrayResultsAbs' => $arrayResultsAbs,
            'arrayResultsDoc' => $arrayResultsDoc,
            'resultSetAbstracts' => $data,
            'resultSetDocuments' => $resultSetDocuments,
            'entityName' => $entityName,
            'orderBy' => "score",
            'hitsShowed' => $totalHits,
            'meanScore' => $meanScore,
            'medianScore' => $medianScore,
            'rangeScore' => $rangeScore,
            'human' => $human,
            'totalTime' => $totalTime,
            'stringHtml' => $stringHtml,
        ));
    }


    public function searchMutationsAction($whatToSearch, $entityName, $dna, $protein)
    {
        $message="inside searchMutationsAction";
        $entityType="mutations";
        $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(1000);
        $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $searchNested->setParam('query', $entityName);

        if($whatToSearch=="snps"){
            $searchNested->setParam('fields', array('snps.mention'));
        }else{
            $searchNested->setParam('fields', array('mutations.mention'));
        }

        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        if($whatToSearch=="snps"){
            $nestedQuery->setPath('snps');
        }else{
            $nestedQuery->setPath('mutations');
        }

        $queryBool->addMust($nestedQuery);

        if($whatToSearch!="snps"){
            //Second query to search inside nested genes.ontologyId to see if it's a human gene
            $searchNested2 = new \Elastica\Query\QueryString();
            $searchNested2->setParam('fields', array('mutations.mutationClass'));
            if($whatToSearch=="substitutions"){
                $searchNested2->setParam('query', "Substitution");
            }elseif($whatToSearch=="insertions"){
                $searchNested2->setParam('query', "Insertion");
            }elseif($whatToSearch=="deletions"){
                $searchNested2->setParam('query', "Deletion");
            }elseif($whatToSearch=="indels"){
                $searchNested2->setParam('query', "InDel");
            }elseif($whatToSearch=="frameshifts"){
                $searchNested2->setParam('query', "Frame_shift");
            }
            $nestedQuery2 = new \Elastica\Query\Nested();
            $nestedQuery2->setQuery($searchNested2);
            $nestedQuery2->setPath('mutations');

            $queryBool->AddMust($nestedQuery2);

        }

        $elasticaQuery->setQuery($queryBool);


        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();

        $paginator = $this->get('ideup.simple_paginator');
        $arrayResultsAbs = $paginator
            //->setMaxPagerItems($this->container->getParameter('etoxMicrome.number_of_pages'), 'abstracts')
            ->setMaxPagerItems(15, 'abstracts')
            //->setItemsPerPage($this->container->getParameter('etoxMicrome.evidences_per_page'), 'abstracts')
            ->setItemsPerPage(10, 'abstracts')
            ->paginate($arrayAbstracts,'abstracts')
            ->getResult()
        ;
        ############### Uncomment when a SCORE has been added to the elasticsearch entries
        //$meanScore=$this->getMmmrScore($data, 'score', 'mean');
        //$medianScore=$this->getMmmrScore($data, $orderBy, 'median');
        //$rangeScore=$this->getMmmrScore($data, $orderBy, 'range');
        //$finderDoc=false;
        ############### Comment when a SCORE has been added to the elasticsearch entries
        $meanScore=0;
        $medianScore=0;
        $rangeScore=0;

        $resultSetDocuments = array();
        $arrayResultsDoc = array();

        $stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);


        return $this->render('MelanomamineFrontendBundle:Search:results.html.twig', array(
            'entityType' => $entityType,
            'whatToSearch' => $whatToSearch,
            'entityName' => $entityName,
            'arrayResultsAbs' => $arrayResultsAbs,
            'arrayResultsDoc' => $arrayResultsDoc,
            'resultSetAbstracts' => $data,
            'resultSetDocuments' => $resultSetDocuments,
            'entityName' => $entityName,
            'orderBy' => "score",
            'hitsShowed' => $totalHits,
            'meanScore' => $meanScore,
            'medianScore' => $medianScore,
            'rangeScore' => $rangeScore,
            'dna' => $dna,
            'protein' => $protein,
            'totalTime' => $totalTime,
            'stringHtml' => $stringHtml,
        ));

    }

    public function searchChemicalsAction($whatToSearch, $entityName)
    {
        $entityType="chemicals";
        $message="inside searchChemicalsAction";
        $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');


        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(1000);
        $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));
        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $searchNested->setParam('query', $entityName);
        $searchNested->setParam('fields', array('chemicals.mention'));

        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        $nestedQuery->setPath('chemicals');

        $queryBool->addMust($nestedQuery);

        $elasticaQuery->setQuery($queryBool);


        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();

        $paginator = $this->get('ideup.simple_paginator');
        $arrayResultsAbs = $paginator
            //->setMaxPagerItems($this->container->getParameter('etoxMicrome.number_of_pages'), 'abstracts')
            ->setMaxPagerItems(15, 'abstracts')
            //->setItemsPerPage($this->container->getParameter('etoxMicrome.evidences_per_page'), 'abstracts')
            ->setItemsPerPage(10, 'abstracts')
            ->paginate($arrayAbstracts,'abstracts')
            ->getResult()
        ;
        ############### Uncomment when a SCORE has been added to the elasticsearch entries
        //$meanScore=$this->getMmmrScore($data, 'score', 'mean');
        //$medianScore=$this->getMmmrScore($data, $orderBy, 'median');
        //$rangeScore=$this->getMmmrScore($data, $orderBy, 'range');
        //$finderDoc=false;
        ############### Comment when a SCORE has been added to the elasticsearch entries
        $meanScore=0;
        $medianScore=0;
        $rangeScore=0;

        $resultSetDocuments = array();
        $arrayResultsDoc = array();

        $stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

        return $this->render('MelanomamineFrontendBundle:Search:results.html.twig', array(
            'entityType' => $entityType,
            'whatToSearch' => $whatToSearch,
            'entityName' => $entityName,
            'arrayResultsAbs' => $arrayResultsAbs,
            'arrayResultsDoc' => $arrayResultsDoc,
            'resultSetAbstracts' => $data,
            'resultSetDocuments' => $resultSetDocuments,
            'entityName' => $entityName,
            'orderBy' => "score",
            'hitsShowed' => $totalHits,
            'meanScore' => $meanScore,
            'medianScore' => $medianScore,
            'rangeScore' => $rangeScore,
            'totalTime' => $totalTime,
            'stringHtml' => $stringHtml,
        ));
    }

    public function searchDiseasesAction($whatToSearch, $entityName)
    {
        $message="inside searchDiseasesAction";
        $entityType="diseases";
        $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(1000);
        $elasticaQuery->setSort(array('melanoma_score_new' => array('order' => 'desc')));
        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $searchNested->setParam('query', $entityName);

        if($whatToSearch=="name"){
            $searchNested->setParam('fields', array('diseases2.mention'));
        }else{
            $searchNested->setParam('fields', array('diseases2.ontologyId'));
        }

        //if whatToSearch is meshId or OMMIMid then we have to set another nested search for the ontology


        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        $nestedQuery->setPath('diseases2');

        $queryBool->addMust($nestedQuery);

        //Second nested query only in case an ontology is been queried
        if ($whatToSearch != "name"){
            if($whatToSearch=="ommimId"){
                $ontology="OMIM";
            }elseif($whatToSearch=="meshId"){
                $ontology="MESH";
            }
            $searchNested2 = new \Elastica\Query\QueryString();
            $searchNested2->setParam('query', $ontology);

            $searchNested2->setParam('fields', array('diseases2.ontology'));

            //if whatToSearch is meshId or OMMIMid then we have to set another nested search for the ontology

            $nestedQuery2 = new \Elastica\Query\Nested();
            $nestedQuery2->setQuery($searchNested2);
            $nestedQuery2->setPath('diseases2');

            $queryBool->addMust($nestedQuery2);
        }


        $elasticaQuery->setQuery($queryBool);

        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();

        $paginator = $this->get('ideup.simple_paginator');
        $arrayResultsAbs = $paginator
            //->setMaxPagerItems($this->container->getParameter('etoxMicrome.number_of_pages'), 'abstracts')
            ->setMaxPagerItems(15, 'abstracts')
            //->setItemsPerPage($this->container->getParameter('etoxMicrome.evidences_per_page'), 'abstracts')
            ->setItemsPerPage(10, 'abstracts')
            ->paginate($arrayAbstracts,'abstracts')
            ->getResult()
        ;
        ############### Uncomment when a SCORE has been added to the elasticsearch entries
        //$meanScore=$this->getMmmrScore($data, 'score', 'mean');
        //$medianScore=$this->getMmmrScore($data, $orderBy, 'median');
        //$rangeScore=$this->getMmmrScore($data, $orderBy, 'range');
        //$finderDoc=false;
        ############### Comment when a SCORE has been added to the elasticsearch entries
        $meanScore=0;
        $medianScore=0;
        $rangeScore=0;

        $resultSetDocuments = array();
        $arrayResultsDoc = array();

        $stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

        return $this->render('MelanomamineFrontendBundle:Search:results.html.twig', array(
            'entityType' => $entityType,
            'whatToSearch' => $whatToSearch,
            'entityName' => $entityName,
            'arrayResultsAbs' => $arrayResultsAbs,
            'arrayResultsDoc' => $arrayResultsDoc,
            'resultSetAbstracts' => $data,
            'resultSetDocuments' => $resultSetDocuments,
            'entityName' => $entityName,
            'orderBy' => "score",
            'hitsShowed' => $totalHits,
            'meanScore' => $meanScore,
            'medianScore' => $medianScore,
            'rangeScore' => $rangeScore,
            'totalTime' => $totalTime,
            'stringHtml' => $stringHtml,
        ));

    }

    public function searchMutatedProteinsAction($whatToSearch, $entityName, $human)
    {
        $message="inside searchMutatedProteinsAction";
        $entityType="mutatedProteins";
        $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(1000);
        $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $searchNested->setParam('query', $entityName);
        if($whatToSearch=="proteinName"){
            $searchNested->setParam('fields', array('mutatedProteins3.mention'));
        }elseif($whatToSearch=="mutationName"){
            $searchNested->setParam('fields', array('mutatedProteins3.geneMention'));
        }elseif($whatToSearch=="uniprotAccession"){
            $searchNested->setParam('fields', array('mutatedProteins3.uniprotAccession'));
        }elseif($whatToSearch=="geneId"){
            $searchNested->setParam('fields', array('mutatedProteins3.ncbiGenId'));
        }


        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        $nestedQuery->setPath('mutatedProteins3');

        $queryBool->addMust($nestedQuery);

        //Second query to search inside nested genes.ontologyId to see if it's a human gene
        $searchNested2 = new \Elastica\Query\QueryString();
        $searchNested2->setParam('query', 9606);
        $searchNested2->setParam('fields', array('mutatedProteins3.ncbiTaxId'));

        $nestedQuery2 = new \Elastica\Query\Nested();
        $nestedQuery2->setQuery($searchNested2);
        $nestedQuery2->setPath('mutatedProteins3');

        if ($human=="human"){
            $queryBool->addMust($nestedQuery2);
        }elseif($human=="not_human"){
            $queryBool->addMustNot($nestedQuery2);
        }

        $elasticaQuery->setQuery($queryBool);


        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();
        $paginator = $this->get('ideup.simple_paginator');
        $arrayResultsAbs = $paginator
            //->setMaxPagerItems($this->container->getParameter('etoxMicrome.number_of_pages'), 'abstracts')
            ->setMaxPagerItems(15, 'abstracts')
            //->setItemsPerPage($this->container->getParameter('etoxMicrome.evidences_per_page'), 'abstracts')
            ->setItemsPerPage(10, 'abstracts')
            ->paginate($arrayAbstracts,'abstracts')
            ->getResult()
        ;
        ############### Uncomment when a SCORE has been added to the elasticsearch entries
        //$meanScore=$this->getMmmrScore($data, 'score', 'mean');
        //$medianScore=$this->getMmmrScore($data, $orderBy, 'median');
        //$rangeScore=$this->getMmmrScore($data, $orderBy, 'range');
        //$finderDoc=false;
        ############### Comment when a SCORE has been added to the elasticsearch entries
        $meanScore=0;
        $medianScore=0;
        $rangeScore=0;

        $resultSetDocuments = array();
        $arrayResultsDoc = array();
        $stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

        return $this->render('MelanomamineFrontendBundle:Search:results.html.twig', array(
            'entityType' => $entityType,
            'whatToSearch' => $whatToSearch,
            'entityName' => $entityName,
            'arrayResultsAbs' => $arrayResultsAbs,
            'arrayResultsDoc' => $arrayResultsDoc,
            'resultSetAbstracts' => $data,
            'resultSetDocuments' => $resultSetDocuments,
            'entityName' => $entityName,
            'orderBy' => "score",
            'hitsShowed' => $totalHits,
            'meanScore' => $meanScore,
            'medianScore' => $medianScore,
            'rangeScore' => $rangeScore,
            'totalTime' => $totalTime,
            'human' => $human,
            'stringHtml' => $stringHtml,
        ));

    }
}
