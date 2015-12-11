<?php

namespace Melanomamine\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use \Elastica9205\Request;



class SearchController extends Controller
{

    public function escapeElasticReservedChars($string) {
        $regex = "/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/";
        return preg_replace($regex, addslashes('\\$0'), $string);
    }

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

    public function insertMention($dictionarySummary,$field, $mention){
        //First of all we search for the $field key inside dictionary
        if (array_key_exists($field, $dictionarySummary)){
            //We search for the correct place.
            //First we search if the mention exists
            $dictionaryField=$dictionarySummary[$field];
            if(array_key_exists($mention, $dictionaryField)){
                //If the mention already exists, we count this new mention.
                $counter=$dictionaryField[$mention];
                $dictionaryField[$mention]=$counter+1;
                $dictionarySummary[$field]=$dictionaryField;
            }else{
                //We create a new entry for this mention
                $dictionaryField[$mention]=1;
                $dictionarySummary[$field]=$dictionaryField;
            }
        }else{
            //Generate a new entry for it in the specified field
            $tmpDictionary=[];
            $tmpDictionary[$mention]=1;
            $dictionarySummary[$field]=$tmpDictionary;
        }

        return $dictionarySummary;
    }

    public function createSummaryTable($arrayResults){
        $message="inside createSummaryTable";
        //We have to iterate over results in $arrayResults and generate an structure to handle this information
        //dictionarySummary["genes"]=dictionaryGenes
        //dictionarySummary["mutations"]=dictionaryMutations   ...and so on

        //dictionaryGenes should have :  dictionaryGenes[gene1]=counter1, dictionaryGenes[gene2]=counter2....    .... dictionaryGenes[genen]=countern
        // and so on...
        $dictionarySummary=[];
        $dictionarySummarySorted=[];
        foreach($arrayResults as $result){
            $source=$result->getSource();
            if ( array_key_exists("genes2", $source) ){
                $arrayGenes=$source["genes2"];
                foreach($arrayGenes as $gene){
                    $mention=$gene["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"genes2", $mention);
                }
            }
            if ( array_key_exists("mutations", $source) ){
                $arrayMutations=$source["mutations"];
                foreach($arrayMutations as $mutation){
                    $mention=$mutation["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"mutations", $mention);
                }
            }
            if ( array_key_exists("chemicals2", $source) ){
                $arrayChemicals=$source["chemicals2"];
                foreach($arrayChemicals as $chemical){
                    $mention=$chemical["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"chemicals2", $mention);
                }
            }
            if ( array_key_exists("diseases2", $source) ){
                $arrayDiseases=$source["diseases2"];
                foreach($arrayDiseases as $disease){
                    $mention=$disease["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"diseases2", $mention);
                }
            }

            if ( array_key_exists("mutatedProteins3", $source) ){
                $mutatedProteins=$source["mutatedProteins3"];
                foreach($mutatedProteins as $mutatedProtein){
                    $mention=$mutatedProtein["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"mutatedProteins3", $mention);
                }
            }
        }
        //We have to short inner dictionaries and create the stringTable to return
        if(count($dictionarySummary)!=0){
            $stringTable="<table class='summaryTable'>";

            if ( array_key_exists("genes2", $dictionarySummary) ){
                $arrayGenes=$dictionarySummary["genes2"];
                arsort($arrayGenes);

                $stringTable.="<tr><th>Genes</th><td>";
                foreach ($arrayGenes as $key => $value){
                    $stringTable.="$key: $value, ";
                }
                $stringTable.="</td></tr>";
            }
            if ( array_key_exists("mutations", $dictionarySummary) ){
                $arrayMutations=$dictionarySummary["mutations"];
                arsort($arrayMutations);

                $stringTable.="<tr><th>Mutations</th><td>";
                foreach ($arrayMutations as $key => $value){
                    $stringTable.="$key: $value, ";
                }
                $stringTable.="</td></tr>";
            }
            if ( array_key_exists("chemicals2", $dictionarySummary) ){
                $arrayChemicals=$dictionarySummary["chemicals2"];
                arsort($arrayChemicals);

                $stringTable.="<tr><th>Chemicals</th><td>";
                foreach ($arrayChemicals as $key => $value){
                    $stringTable.="$key: $value, ";
                }
                $stringTable.="</td></tr>";
            }
            if ( array_key_exists("diseases2", $dictionarySummary) ){
                $arrayDiseases=$dictionarySummary["diseases2"];
                arsort($arrayDiseases);

                $stringTable.="<tr><th>Diseases</th><td>";
                foreach ($arrayDiseases as $key => $value){
                    $stringTable.="$key: $value, ";
                }
                $stringTable.="</td></tr>";
            }

            if ( array_key_exists("mutatedProteins3", $source) ){
                $arrayMutatedProteins=$dictionarySummary["mutatedProteins3"];
                arsort($arrayMutatedProteins);

                $stringTable.="<tr><th>Mutated Proteins</th><td>";
                foreach ($arrayMutatedProteins as $key => $value){
                    $stringTable.="$key: $value, ";
                }
                $stringTable.="</td></tr>";
            }
            $stringTable.="</table>";
        }


        //ld($dictionarySummarySorted);


        return $stringTable;

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

        $summaryTable = $this->createSummaryTable($arrayAbstracts);

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

        //$stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

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
            'summaryTable' => $summaryTable,
            //'stringHtml' => $stringHtml,
        ));
    }

    public function getAliases($ncbiGeneId, $queryType){
        $message="inside getAliases";
        $finder = $this->container->get('fos_elastica.index.melanomamine.genesDictionary');
        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(1000);
        $queryString = new \Elastica\Query\QueryString();
        $queryString->setParam('query', $ncbiGeneId);
        $queryString->setParam('fields', array('ncbiGeneId'));
        $elasticaQuery->setQuery($queryString);
        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $arrayResults=$data->getResults();
        //Now we generate an array with the Aliases:
        $arrayAliases=[];
        foreach($arrayResults as $result){
            $source=$result->getSource();
            array_push($arrayAliases, $source["geneProteinName"]);
        }
        return($arrayAliases);
    }

    public function disambiguationProcess($entityName, $whatToSearch, $specie, $queryType)
    {
        $message="inside getArrayQueryExpansion";
        if($queryType=="geneProtein"){
            $entityType="genes";
        }elseif($queryType=="proteinMutated"){
            $entityType="mutatedProteins";
        }
        $finder = $this->container->get('fos_elastica.index.melanomamine.genesDictionary');

        $elasticaQuery = new \Elastica\Query();
        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside geneProteinName
        $queryString = new \Elastica\Query\QueryString();
        $queryString->setParam('query', $entityName);
        $queryString->setParam('fields', array('geneProteinName'));

        $queryBool->addMust($queryString);

        /*
        //Second query to search inside ncbiTaxId
        $queryString2 = new \Elastica\Query\QueryString();
        $queryString2->setParam('query', 9606);
        $queryString2->setParam('fields', array('ncbiTaxId'));

        $queryBool->addMust($queryString2);
        */
        $elasticaQuery->setQuery($queryBool);

        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayResults=$data->getResults();


        //We generate an structure for disambiguation interface. Including Name-ncbiGenId-Array-of-Aliases.

        $arrayNameIdAliases=[];
        foreach($arrayResults as $result){
            $source=$result->getSource();
            $ncbiGeneId=$source["ncbiGeneId"];
            $tmpDictionary["geneProteinName"]=$source["geneProteinName"];
            $tmpDictionary["ncbiGeneId"]=$ncbiGeneId;
            $tmpDictionary["ncbiTaxId"]=$source["ncbiTaxId"];
            $arrayAliases=$this->getAliases($ncbiGeneId, $queryType);
            $tmpDictionary["arrayAliases"]=$arrayAliases;

            array_push($arrayNameIdAliases, $tmpDictionary);
        }
        //If theres is only one element inside arrayNameIdAliases, There is no need for disambiguation
        $message="Go to disambiguation interface";
        return $this->render('MelanomamineFrontendBundle:Search:disambiguation.html.twig', array(
            'entityName' => $entityName,
            'entityType' => $entityType,
            'whatToSearch' => $whatToSearch,
            'arrayNameIdAliases' => $arrayNameIdAliases,
            'specie' => $specie,

        ));



        //En $ncbiGeneId tenemos el id para realizar la búsqueda de la query expansion...

        /*
        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(1000);
        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside geneProteinName
        $queryString = new \Elastica\Query\QueryString();
        $queryString->setParam('query', $ncbiGeneId);
        $queryString->setParam('fields', array('ncbiGeneId'));

        $queryBool->addMust($queryString);

        //Second query to search inside ncbiTaxId
        $queryString2 = new \Elastica\Query\QueryString();
        $queryString2->setParam('query', 9606);
        $queryString2->setParam('fields', array('ncbiTaxId'));

        $queryBool->addMust($queryString2);

        $elasticaQuery->setQuery($queryBool);
        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        ld($totalHits);
        $totalTime = $data->getTotalTime();
        $arrayResults=$data->getResults();

        //We generate
        ldd($arrayGenes);
        */

    }

    public function performBasicSearch($searchTerm, $fieldToSearch, $type){
        $message="inside performBasicSearch";
        //ld($type);
        //ld($searchTerm);
        //ld($fieldToSearch);
        $finder = $this->container->get('fos_elastica.index.melanomamine.'.$type);
        $elasticaQuery = new \Elastica\Query();
        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside geneProteinName
        $queryString = new \Elastica\Query\QueryString();
        $searchTerm = $this->escapeElasticReservedChars($searchTerm);
        $queryString->setParam('query', $searchTerm);
        $queryString->setParam('fields', array($fieldToSearch));
        //ld($queryString);
        $queryBool->addMust($queryString);

        $elasticaQuery->setQuery($queryBool);

        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayResults=$data->getResults();

        //Now we have to iterate over the values of these results and repeat the search

        return($arrayResults);
    }

    public function performNestedSearch($entityName, $type, $mapping, $property){
        $message="inside performNestedSearch";
        $finder = $this->container->get('fos_elastica.index.melanomamine.'.$type);
        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(1000);
        $elasticaQuery->setSort(array('melanoma_score_new' => array('order' => 'desc')));

        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $entityName=$this->escapeElasticReservedChars($entityName);
        $searchNested->setParam('query', $entityName);
        $searchNested->setParam('fields', array($mapping.'.'.$property));

        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        $nestedQuery->setPath($mapping);

        $queryBool->addMust($nestedQuery);

        $elasticaQuery->setQuery($queryBool);


        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();
        //ld($arrayAbstracts);
        return $arrayAbstracts;

    }

    public function getChemicalsQueryExpansion($entityName){
        $message="inside getChemicalsQueryExpansion";
        $arrayAliases=[];
        array_push($arrayAliases, $entityName);
        //ld($entityName);
        $entityName = $this->escapeElasticReservedChars($entityName);
        $finder = $this->container->get('fos_elastica.index.melanomamine.chemicalsDictionary');
        $entityType="chemicals";


        $elasticaQuery = new \Elastica\Query();
        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside geneProteinName
        $queryString = new \Elastica\Query\QueryString();
        $queryString->setParam('query', $entityName);
        $queryString->setParam('fields', array('chemicalName'));

        $queryBool->addMust($queryString);

        $elasticaQuery->setQuery($queryBool);

        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayResults=$data->getResults();
        //Now we have to iterate over the values of these results and repeat the search. We focus in chebiId field

        foreach($arrayResults as $result){
            $chebi = $result->getSource()["chebi"];
            //ld($chebi);
            if( $chebi != ""){
                $arrayTmp=$this->performBasicSearch($chebi,"chebi","chemicalsDictionary");
                //$arrayTmp=$this->performBasicSearch("vemurafenib","chemicalName","chemicalsDictionary");
                //$arrayTmp=$this->performBasicSearch("CHEBI\:63637","chebi","chemicalsDictionary");
                //ld($arrayTmp);
                foreach($arrayTmp as $tmpResult){
                    //ld($tmpResult);
                    $alias=$tmpResult->getSource()["chemicalName"];
                    array_push($arrayAliases, $alias);
                }
            }
        }
        //ld($arrayAliases);
        //Delete duplicates:
        $arrayAliases=array_unique($arrayAliases);
        return $arrayAliases;
    }

    public function searchGenesAction($whatToSearch, $entityName, $specie)
    {
        $entityType="genes";
        $message="inside searchGenesAction";


        if ($whatToSearch=="geneProteinName"){
            #First of all we check for query expansion. But we only do this in geneProteinName searches and never for ncbiGeneId
            if ($specie=="queryExpansion"){
                return $arrayQueryExpanded=$this->disambiguationProcess($entityName, $whatToSearch, $specie, "geneProtein");
                ##########################################################################################
                ##############Stops here going through queryExpansion disambiguation process##############
                ##########################################################################################
            }
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
            $elasticaQuery->setQuery($queryBool);

            if ($specie=="all"){
                //No need for specie restriction:
                $data = $finder->search($elasticaQuery);
                $totalHits = $data->getTotalHits();
                $totalTime = $data->getTotalTime();
                $arrayAbstracts=$data->getResults();


            }else{
                //We need specie restriction
                if ($specie=="human"){
                    $specie="9606";
                }
                //Second query to search inside nested genes.ontologyId to see if it's a human gene
                $searchNested2 = new \Elastica\Query\QueryString();
                $searchNested2->setParam('query', $specie);
                $searchNested2->setParam('fields', array('genes.ontologyId'));

                $nestedQuery2 = new \Elastica\Query\Nested();
                $nestedQuery2->setQuery($searchNested2);
                $nestedQuery2->setPath('genes');

                $queryBool->addMust($nestedQuery2);

                $elasticaQuery->setQuery($queryBool);


                $data = $finder->search($elasticaQuery);
                $totalHits = $data->getTotalHits();
                $totalTime = $data->getTotalTime();
                $arrayAbstracts=$data->getResults();
            }
        }
        elseif($whatToSearch=="ncbiGeneId"){
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

            if ($specie=="human"){
                $queryBool->addMust($nestedQuery2);
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

        //$stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

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
            'specie' => $specie,
            'totalTime' => $totalTime,
            //'stringHtml' => $stringHtml,
        ));
    }


    public function searchGenesExpandedAction($whatToSearch, $entityName, $specie, $searchTerm)
    {
        $entityType="genes";
        $message="inside searchGenesExpandedAction";
        //At this time, $entityName contains the ncbiGeneId
        $arrayAliases=$this->getAliases($entityName, "whatever?");

        //Even though the entityName contains the ncbiGeneId, this is not the term that the user has searched for. We need to get it in order to show it at results template


        $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(1000);
        $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $searchNested->setParam('query', $entityName);
        $searchNested->setParam('fields', array('genes2.ontology')); //shouldn't be ontology field!! Re-insert data into genes3. ncbiGeneId

        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        $nestedQuery->setPath('genes2');

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

        return $this->render('MelanomamineFrontendBundle:Search:results_query_expanded.html.twig', array(
            'entityType' => $entityType,
            'whatToSearch' => $whatToSearch,
            'entityName' => $entityName,
            'searchTerm' => $searchTerm,
            'arrayResultsAbs' => $arrayResultsAbs,
            'arrayResultsDoc' => $arrayResultsDoc,
            'resultSetAbstracts' => $data,
            'resultSetDocuments' => $resultSetDocuments,
            'orderBy' => "score",
            'hitsShowed' => $totalHits,
            'totalHits' => $totalHits,
            'meanScore' => $meanScore,
            'medianScore' => $medianScore,
            'rangeScore' => $rangeScore,
            'specie' => $specie,
            'totalTime' => $totalTime,
            'arrayAliases' => $arrayAliases,
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
            //Second query to search for the type of mutation:
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
        //We need a third query to search for dna/protein mutations
        /*
        if($whatToSearch!="snps"){
            //Second query to search for the type of mutation:
            $searchNested3 = new \Elastica\Query\QueryString();
            $searchNested3->setParam('fields', array('mutations.sequenceClass'));

            $searchNested3->setParam('query', "Frame_shift");
            $nestedQuery3 = new \Elastica\Query\Nested();
            $nestedQuery3->setQuery($searchNested3);
            $nestedQuery3->setPath('mutations');

            $queryBool->AddMust($nestedQuery3);

        }*/

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

        //$stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);


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
            //'stringHtml' => $stringHtml,
        ));

    }

    public function searchChemicalsAction($whatToSearch, $entityName, $queryExpansion)
    {
        $entityType="chemicals";
        $message="inside searchChemicalsAction";
        //ldd($queryExpansion);
        # Desambiguation is not needed for Chemical queryExpansion.
        if($queryExpansion=="true"){
            #In this case we make a query expansion for the chemicalName and retrieve an array of entityNames to search form
            $arrayEntityName=$this->getChemicalsQueryExpansion($entityName);
            //ld($arrayEntityName);
            //We should retrieve al the abstracts for any of the entityName inside the $arrayEntityName
            $arrayAbstracts=[];
            foreach($arrayEntityName as $entityName){
                $tmpResults=$this->performNestedSearch($entityName, "abstracts", "chemicals2", "mention");//performNestedSearch($entityName, $type, $mapping, $property)
                $arrayAbstracts=array_merge($arrayAbstracts,$tmpResults);
            }
            $totalHits=count($arrayAbstracts);
            $totalTime=0;

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

            //$stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

            return $this->render('MelanomamineFrontendBundle:Search:results_query_expanded.html.twig', array(
                'entityType' => $entityType,
                'whatToSearch' => $whatToSearch,
                'entityName' => $entityName,
                'arrayResultsAbs' => $arrayResultsAbs,
                'arrayResultsDoc' => $arrayResultsDoc,
                'resultSetDocuments' => $resultSetDocuments,
                'entityName' => $entityName,
                'orderBy' => "score",
                'hitsShowed' => $totalHits,
                'meanScore' => $meanScore,
                'medianScore' => $medianScore,
                'rangeScore' => $rangeScore,
                'totalTime' => $totalTime,
                'totalHits' => $totalHits,
                'queryExpansion' => $queryExpansion,
                //'stringHtml' => $stringHtml,
            ));


        }elseif($queryExpansion=="false"){
            #We just search for entityName. No query expansion needeed
            $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

            $elasticaQuery = new \Elastica\Query();
            $elasticaQuery->setSize(1000);
            $elasticaQuery->setSort(array('melanoma_score_new' => array('order' => 'desc')));
            //BoolQuery to load 2 queries.
            $queryBool = new \Elastica\Query\BoolQuery();

            //First query to search inside nested genes.mention
            $searchNested = new \Elastica\Query\QueryString();
            //We escape entityName but keep it into another variable "searchName" in order to not modify entityName. It will be passed later on to template.
            $searchName = $this->escapeElasticReservedChars($entityName);
            $searchNested->setParam('query', $searchName);
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

        //$stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

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
            //'stringHtml' => $stringHtml,
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

        //$stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

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
            //'stringHtml' => $stringHtml,
        ));

    }

    public function searchMutatedProteinsAction($whatToSearch, $entityName, $specie)
    {
        $message = "inside searchMutatedProteinsAction";
        if($whatToSearch == "proteinName" && $specie == "queryExpansion"){
            return $arrayQueryExpanded=$this->disambiguationProcess($entityName, $whatToSearch, $specie, "proteinMutated");
        }
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
            $searchNested->setParam('fields', array('mutatedProteins3.geneMention'));
        }elseif($whatToSearch=="mutationName"){
            $searchNested->setParam('fields', array('mutatedProteins3.mention'));
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
        $searchNested2->setParam('query', $human);
        $searchNested2->setParam('fields', array('mutatedProteins3.ncbiTaxId'));

        $nestedQuery2 = new \Elastica\Query\Nested();
        $nestedQuery2->setQuery($searchNested2);
        $nestedQuery2->setPath('mutatedProteins3');

        if ($human=="human"){
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
        //$stringHtml = $this->getStringHtmlResults($arrayResultsAbs, $entityName);

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
            //'stringHtml' => $stringHtml,
        ));

    }
}
