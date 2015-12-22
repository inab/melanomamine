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

    public function createSummaryTable($arrayResults, $entityName){
        $message="inside createSummaryTable";
        //We have to iterate over results in $arrayResults and generate an structure to handle this information
        //dictionarySummary["genes"]=dictionaryGenes
        //dictionarySummary["mutations"]=dictionaryMutations   ...and so on

        //dictionaryGenes should have :  dictionaryGenes[gene1]=counter1, dictionaryGenes[gene2]=counter2....    .... dictionaryGenes[genen]=countern
        // and so on...
        $dictionarySummary=[];
        $dictionarySummarySorted=[];
        $arraySummaryTitles=[];

        foreach($arrayResults as $result){
            $source=$result->getSource();
            $pmid=$source["pmid"];
            $dictionaryTmp=[];
            if ( array_key_exists("genes3", $source) ){
                $arrayGenes=$source["genes3"];
                foreach($arrayGenes as $gene){
                    $mention=$gene["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"genes3", $mention);
                    $dictionaryTmp=$this->insertMention($dictionaryTmp,"genes", $mention);
                }
            }
            if ( array_key_exists("mutations2", $source) ){
                $arrayMutations=$source["mutations2"];
                foreach($arrayMutations as $mutation){
                    $mention=$mutation["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"mutations2", $mention);
                    $dictionaryTmp=$this->insertMention($dictionaryTmp,"mutations", $mention);
                }
            }
            if ( array_key_exists("chemicals2", $source) ){
                $arrayChemicals=$source["chemicals2"];
                foreach($arrayChemicals as $chemical){
                    $mention=$chemical["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"chemicals2", $mention);
                    $dictionaryTmp=$this->insertMention($dictionaryTmp,"chemicals", $mention);
                }
            }
            if ( array_key_exists("diseases3", $source) ){
                $arrayDiseases=$source["diseases3"];
                foreach($arrayDiseases as $disease){
                    $mention=$disease["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"diseases3", $mention);
                    $dictionaryTmp=$this->insertMention($dictionaryTmp,"diseases", $mention);
                }
            }
            if ( array_key_exists("mutatedProteins4", $source) ){
                $mutatedProteins=$source["mutatedProteins4"];
                foreach($mutatedProteins as $mutatedProtein){
                    $mention=$mutatedProtein["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"mutatedProteins4", $mention);
                    $dictionaryTmp=$this->insertMention($dictionaryTmp,"mutatedProteins", $mention);
                }
            }
            if ( array_key_exists("species2", $source) ){
                $species=$source["species2"];
                foreach($species as $specie){
                    $mention=$specie["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"species2", $mention);
                    $dictionaryTmp=$this->insertMention($dictionaryTmp,"species", $mention);
                }
            }
            if ( array_key_exists("mutations2", $source) ){
                $mutations=$source["mutations2"];
                foreach($mutations as $mutation){
                    $mention=$mutation["mention"];
                    $dictionarySummary=$this->insertMention($dictionarySummary,"mutations2", $mention);
                    $dictionaryTmp=$this->insertMention($dictionaryTmp,"mutations", $mention);
                }
            }
            arsort($dictionaryTmp);
            $arraySummaryTitles[$pmid]=$dictionaryTmp;
        }

        //We have to short inner dictionaries and create the stringTable to return


        if(count($dictionarySummary)!=0){
            $stringTable="<table class='summaryTable'>";
            $stringCSV="";  //in stringCSV we generate the content of the CSV file that will be downloaded upon user request
            if ( array_key_exists("genes3", $dictionarySummary) ){
                $arrayGenes=$dictionarySummary["genes3"];
                arsort($arrayGenes);

                $stringTable.="<tr><th>Genes</th><td><span class='more'>";
                $stringCSV.="GENES\tAppearances\n";
                foreach ($arrayGenes as $key => $value){
                    $stringTable.="$key: $value, ";
                    $stringCSV.="$key\t$value\n";
                }
                $stringTable.="</span></td></tr>";
                $stringCSV.="\n";
            }
            if ( array_key_exists("mutations2", $dictionarySummary) ){
                $arrayMutations=$dictionarySummary["mutations2"];
                arsort($arrayMutations);

                $stringTable.="<tr><th>Mutations</th><td><span class='more'>";
                $stringCSV.="MUTATIONS\tAppearances\n";
                foreach ($arrayMutations as $key => $value){
                    $stringTable.="$key: $value, ";
                    $stringCSV.="$key\t$value\n";
                }
                $stringTable.="</span></td></tr>";
                $stringCSV.="\n";
            }
            if ( array_key_exists("chemicals2", $dictionarySummary) ){
                $arrayChemicals=$dictionarySummary["chemicals2"];
                arsort($arrayChemicals);

                $stringTable.="<tr><th>Chemicals</th><td><span class='more'>";
                $stringCSV.="CHEMICALS\tAppearances\n";
                foreach ($arrayChemicals as $key => $value){
                    $stringTable.="$key: $value, ";
                    $stringCSV.="$key\t$value\n";
                }
                $stringTable.="</span></td></tr>";
                $stringCSV.="\n";
            }
            if ( array_key_exists("diseases3", $dictionarySummary) ){
                $arrayDiseases=$dictionarySummary["diseases3"];
                arsort($arrayDiseases);

                $stringTable.="<tr><th>Diseases</th><td><span class='more'>";
                $stringCSV.="DISEASES\tAppearances\n";
                foreach ($arrayDiseases as $key => $value){
                    $stringTable.="$key: $value, ";
                    $stringCSV.="$key\t$value\n";
                }
                $stringTable.="</span></td></tr>";
                $stringCSV.="\n";
            }
            if ( array_key_exists("mutatedProteins4", $source) ){
                $arrayMutatedProteins=$dictionarySummary["mutatedProteins4"];
                arsort($arrayMutatedProteins);

                $stringTable.="<tr><th>Mutated Proteins</th><td><span class='more'>";
                $stringCSV.="MUTATED PROTEINS\tAppearances\n";
                foreach ($arrayMutatedProteins as $key => $value){
                    $stringTable.="$key: $value, ";
                    $stringCSV.="$key\t$value\n";
                }
                $stringTable.="</span></td></tr>";
                $stringCSV.="\n";
            }
            if ( array_key_exists("species2", $source) ){
                $arraySpecies=$dictionarySummary["species2"];
                arsort($arraySpecies);

                $stringTable.="<tr><th>Species</th><td><span class='more'>";
                $stringCSV.="SPECIES\tAppearances\n";
                foreach ($arraySpecies as $key => $value){
                    $stringTable.="$key: $value, ";
                    $stringCSV.="$key\t$value\n";
                }
                $stringTable.="</span></td></tr>";
                $stringCSV.="\n";
            }
            if ( array_key_exists("mutations2", $source) ){
                $arrayMutations=$dictionarySummary["mutations2"];
                arsort($arrayMutations);

                $stringTable.="<tr><th>Mutations</th><td><span class='more'>";
                $stringCSV.="MUTATIONS\tAppearances\n";
                foreach ($arrayMutations as $key => $value){
                    $stringTable.="$key: $value, ";
                    $stringCSV.="$key\t$value\n";
                }
                $stringTable.="</span></td></tr>";
                $stringCSV.="\n";
            }

            $stringTable.="</table>";
        }

        //ld($dictionarySummarySorted);
        //We create the file with the CSV content
        $zip = new \ZipArchive();
        $path = $this->get('kernel')->getRootDir(). "/../web/files/summaryTables";
        $date=date("Y-m-d_H:i:s");
        $filename = $entityName."-".$date;
        $pathToFile="$path/$filename";
        $pathToZip="$pathToFile.zip";

        if ($zip->open($pathToZip, \ZIPARCHIVE::CREATE )!==TRUE) {
            exit("cannot open <$pathToZip>\n");
        }
        $fp = fopen($pathToFile, 'w');
        fwrite($fp, $stringCSV);
        fclose($fp);
        $zip->addFile($pathToFile,$filename.".csv");
        $zip->close();

        unlink($pathToFile);
        $filename=$filename.".zip";
        //Delete filename keeping only .zip


        $arrayResponse=[];
        $arrayResponse["filename"]=$filename;
        $arrayResponse["summaryTable"]=$stringTable;
        $arrayResponse["summaryTitles"]=$arraySummaryTitles;
        return $arrayResponse;
    }

    public function getAliases($ncbiGeneId, $queryType){
        $message="inside getAliases";
        $finder = $this->container->get('fos_elastica.index.melanomamine.genesDictionary');
        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(500);
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
        $elasticaQuery->setSize(500);
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
        $elasticaQuery->setSize(500);
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

    public function searchKeywordsAction($whatToSearch,$entityName)
    {

        $entityType="keywords";
        $message="inside searchKeywordAction";


        $elasticaQuery  = new \Elastica\Query();
        $elasticaQuery->setSize(500);
        $elasticaQuery->setSort(array('melanoma_score_new' => array('order' => 'desc')));

        $queryString  = new \Elastica\Query\QueryString();
        //'And' or 'Or' default : 'Or'
        $queryString->setDefaultOperator('AND');
        $queryString->setQuery($entityName);

        if($whatToSearch=="freeText"){


            $elasticaQuery->setQuery($queryString);

        }elseif($whatToSearch=="withGenesProtein"){

            $field = "genes2";//We search in type with snowball analyzer to perform typical keyword search
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withProteinMutations"){

            $field = "mutatedProteins3";//We search in type with snowball analyzer to perform typical keyword search
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withSNPs"){

            $field = "snps";//We search in type with snowball analyzer to perform typical keyword search
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withDNAmutations"){

            $field = "mutations";//We search in type with snowball analyzer to perform typical keyword search
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withChemicals"){

            $field = "chemicals";//We search in type with snowball analyzer to perform typical keyword search
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }elseif($whatToSearch=="withDiseases"){

            $field = "diseases2";//We search in type with snowball analyzer to perform typical keyword search
            $filter = new \Elastica\Filter\Exists($field);
            $filteredQuery = new \Elastica\Query\Filtered($queryString, $filter);
            $elasticaQuery->setQuery($filteredQuery);

        }



        $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();


        $arrayResponse = $this->createSummaryTable($arrayAbstracts, $entityName); //this method returns an array with two contents: the filename where the summaryTable file is saved, and the string with the summaryTable
        $filename = $arrayResponse["filename"];
        $summaryTable = $arrayResponse["summaryTable"];
        $arraySummaryTitles = $arrayResponse["summaryTitles"];

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
            'filenameSummaryTable' => $filename,
            'arraySummaryTitles' => $arraySummaryTitles,

        ));
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
            $elasticaQuery->setSize(500);
            $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

            //BoolQuery to load 2 queries.
            $queryBool = new \Elastica\Query\BoolQuery();

            //First query to search inside nested genes.mention
            $searchNested = new \Elastica\Query\QueryString();
            $searchNested->setParam('query', $entityName);
            $searchNested->setParam('fields', array('genes3.mention'));

            $nestedQuery = new \Elastica\Query\Nested();
            $nestedQuery->setQuery($searchNested);
            $nestedQuery->setPath('genes3');

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
                $searchNested2->setParam('fields', array('genes3.ontologyId'));

                $nestedQuery2 = new \Elastica\Query\Nested();
                $nestedQuery2->setQuery($searchNested2);
                $nestedQuery2->setPath('genes3');

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
            $elasticaQuery->setSize(500);
            $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

            //BoolQuery to load 2 queries.
            $queryBool = new \Elastica\Query\BoolQuery();

            //First query to search inside nested genes.mention
            $searchNested = new \Elastica\Query\QueryString();
            $searchNested->setParam('query', $entityName);
            $searchNested->setParam('fields', array('genes3.ontology'));

            $nestedQuery = new \Elastica\Query\Nested();
            $nestedQuery->setQuery($searchNested);
            $nestedQuery->setPath('genes3');

            $queryBool->addMust($nestedQuery);

            //Second query to search inside nested genes.ontologyId to see if it's a human gene
            $searchNested2 = new \Elastica\Query\QueryString();
            $searchNested2->setParam('query', 9606);
            $searchNested2->setParam('fields', array('genes3.ontologyId'));

            $nestedQuery2 = new \Elastica\Query\Nested();
            $nestedQuery2->setQuery($searchNested2);
            $nestedQuery2->setPath('genes3');
            if ($specie=="human"){
                $queryBool->addMust($nestedQuery2);
            }

            $elasticaQuery->setQuery($queryBool);


            $data = $finder->search($elasticaQuery);
            $totalHits = $data->getTotalHits();
            $totalTime = $data->getTotalTime();
            $arrayAbstracts=$data->getResults();

        }

        $arrayResponse = $this->createSummaryTable($arrayAbstracts, $entityName); //this method returns an array with two contents: the filename where the summaryTable file is saved, and the string with the summaryTable
        $filename = $arrayResponse["filename"];
        $summaryTable = $arrayResponse["summaryTable"];
        $arraySummaryTitles = $arrayResponse["summaryTitles"];

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
            'summaryTable' => $summaryTable,
            'filenameSummaryTable' => $filename,
            'arraySummaryTitles' => $arraySummaryTitles,
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
        $elasticaQuery->setSize(500);
        $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $searchNested->setParam('query', $entityName);
        $searchNested->setParam('fields', array('genes3.ontology')); //shouldn't be ontology field!! Re-insert data into genes3. ncbiGeneId

        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        $nestedQuery->setPath('genes3');

        $queryBool->addMust($nestedQuery);

        $elasticaQuery->setQuery($queryBool);


        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();

        $arrayResponse = $this->createSummaryTable($arrayAbstracts, $entityName); //this method returns an array with two contents: the filename where the summaryTable file is saved, and the string with the summaryTable
        $filename = $arrayResponse["filename"];
        $summaryTable = $arrayResponse["summaryTable"];
        $arraySummaryTitles = $arrayResponse["summaryTitles"];

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
            'summaryTable' => $summaryTable,
            'filenameSummaryTable' => $filename,
            'arraySummaryTitles' => $arraySummaryTitles,
        ));
    }


    public function searchMutationsAction($whatToSearch, $entityName, $dna, $protein)
    {
        $message="inside searchMutationsAction";

        $entityType="mutations";
        $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(500);
        $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));

        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $searchNested->setParam('query', $entityName);

        if($whatToSearch=="snps"){
            $searchNested->setParam('fields', array('snps.mention'));
        }else{
            $searchNested->setParam('fields', array('mutations2.mention'));
        }

        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        if($whatToSearch=="snps"){
            $nestedQuery->setPath('snps');
        }else{
            $nestedQuery->setPath('mutations2');
        }

        $queryBool->addMust($nestedQuery);

        if($whatToSearch!="snps"){
            //Second query to search for the type of mutation:
            $searchNested2 = new \Elastica\Query\QueryString();
            $searchNested2->setParam('fields', array('mutations2.mutationClass'));
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
            $nestedQuery2->setPath('mutations2');

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

        $arrayResponse = $this->createSummaryTable($arrayAbstracts, $entityName); //this method returns an array with two contents: the filename where the summaryTable file is saved, and the string with the summaryTable
        $filename = $arrayResponse["filename"];
        $summaryTable = $arrayResponse["summaryTable"];
        $arraySummaryTitles = $arrayResponse["summaryTitles"];

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
            'summaryTable' => $summaryTable,
            'filenameSummaryTable' => $filename,
            'arraySummaryTitles' => $arraySummaryTitles,
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

            //$arrayResponse=[];
            //$filename="vemurafenib-2015-12-14_18:26:18.zip";
            $arrayResponse = $this->createSummaryTable($arrayAbstracts, $entityName); //this method returns an array with two contents: the filename where the summaryTable file is saved, and the string with the summaryTable
            $filename = $arrayResponse["filename"];
            $summaryTable = $arrayResponse["summaryTable"];
            $arraySummaryTitles = $arrayResponse["summaryTitles"];

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
                'summaryTable' => $summaryTable,
                'filenameSummaryTable' => $filename,
                'arraySummaryTitles' => $arraySummaryTitles,
            ));


        }elseif($queryExpansion=="false"){
            #We just search for entityName. No query expansion needeed
            $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

            $elasticaQuery = new \Elastica\Query();
            $elasticaQuery->setSize(500);
            $elasticaQuery->setSort(array('melanoma_score_new' => array('order' => 'desc')));
            //BoolQuery to load 2 queries.
            $queryBool = new \Elastica\Query\BoolQuery();

            //First query to search inside nested genes.mention
            $searchNested = new \Elastica\Query\QueryString();
            //We escape entityName but keep it into another variable "searchName" in order to not modify entityName. It will be passed later on to template.
            $searchName = $this->escapeElasticReservedChars($entityName);
            $searchNested->setParam('query', $searchName);
            $searchNested->setParam('fields', array('chemicals2.mention'));

            $nestedQuery = new \Elastica\Query\Nested();
            $nestedQuery->setQuery($searchNested);
            $nestedQuery->setPath('chemicals2');

            $queryBool->addMust($nestedQuery);

            $elasticaQuery->setQuery($queryBool);


            $data = $finder->search($elasticaQuery);
            $totalHits = $data->getTotalHits();
            $totalTime = $data->getTotalTime();
            $arrayAbstracts=$data->getResults();
        }

        //$arrayResponse["stringTable"]="";
        //$filename="vemurafenib-2015-12-14_18:26:18.zip";

        $arrayResponse = $this->createSummaryTable($arrayAbstracts, $entityName); //this method returns an array with two contents: the filename where the summaryTable file is saved, and the string with the summaryTable
        $filename = $arrayResponse["filename"];
        $summaryTable = $arrayResponse["summaryTable"];
        $arraySummaryTitles = $arrayResponse["summaryTitles"];

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
            'filenameSummaryTable' => $filename,
            'arraySummaryTitles' => $arraySummaryTitles,
        ));

    }

    public function searchDiseasesAction($whatToSearch, $entityName)
    {
        $message="inside searchDiseasesAction";
        $entityType="diseases";
        $finder = $this->container->get('fos_elastica.index.melanomamine.abstracts');

        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setSize(500);
        $elasticaQuery->setSort(array('melanoma_score_new' => array('order' => 'desc')));
        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $searchNested->setParam('query', $entityName);

        if($whatToSearch=="name"){
            $searchNested->setParam('fields', array('diseases3.mention'));
        }else{
            $searchNested->setParam('fields', array('diseases3.ontologyId'));
        }

        //if whatToSearch is meshId or OMMIMid then we have to set another nested search for the ontology


        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        $nestedQuery->setPath('diseases3');

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

            $searchNested2->setParam('fields', array('diseases3.ontology'));

            //if whatToSearch is meshId or OMMIMid then we have to set another nested search for the ontology

            $nestedQuery2 = new \Elastica\Query\Nested();
            $nestedQuery2->setQuery($searchNested2);
            $nestedQuery2->setPath('diseases3');

            $queryBool->addMust($nestedQuery2);
        }


        $elasticaQuery->setQuery($queryBool);

        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();

        $arrayResponse = $this->createSummaryTable($arrayAbstracts, $entityName); //this method returns an array with two contents: the filename where the summaryTable file is saved, and the string with the summaryTable
        $filename = $arrayResponse["filename"];
        $summaryTable = $arrayResponse["summaryTable"];
        $arraySummaryTitles = $arrayResponse["summaryTitles"];

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
            'filenameSummaryTable' => $filename,
            'arraySummaryTitles' => $arraySummaryTitles,
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
        $elasticaQuery->setSize(500);
        $elasticaQuery->setSort(array('melanoma_score_new' => array('order' => 'desc')));

        //BoolQuery to load 2 queries.
        $queryBool = new \Elastica\Query\BoolQuery();

        //First query to search inside nested genes.mention
        $searchNested = new \Elastica\Query\QueryString();
        $searchNested->setParam('query', $entityName);
        if($whatToSearch=="proteinName"){
            $searchNested->setParam('fields', array('mutatedProteins4.geneMention'));
        }elseif($whatToSearch=="mutationName"){
            $searchNested->setParam('fields', array('mutatedProteins4.mention'));
        }elseif($whatToSearch=="uniprotAccession"){
            $searchNested->setParam('fields', array('mutatedProteins4.uniprotAccession'));
        }elseif($whatToSearch=="geneId"){
            $searchNested->setParam('fields', array('mutatedProteins4.ncbiGenId'));
        }


        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setQuery($searchNested);
        $nestedQuery->setPath('mutatedProteins4');

        $queryBool->addMust($nestedQuery);

        if ($specie=="human"){
            $specie="9606";
        }

        //Second query to search inside nested genes.ontologyId to see if it's a human gene
        $searchNested2 = new \Elastica\Query\QueryString();
        $searchNested2->setParam('query', $specie);
        $searchNested2->setParam('fields', array('mutatedProteins4.ncbiTaxId'));

        $nestedQuery2 = new \Elastica\Query\Nested();
        $nestedQuery2->setQuery($searchNested2);
        $nestedQuery2->setPath('mutatedProteins4');

        if ($specie=="human"){
            $queryBool->addMust($nestedQuery2);
        }

        $elasticaQuery->setQuery($queryBool);

        $data = $finder->search($elasticaQuery);
        $totalHits = $data->getTotalHits();
        $totalTime = $data->getTotalTime();
        $arrayAbstracts=$data->getResults();


        $arrayResponse = $this->createSummaryTable($arrayAbstracts, $entityName); //this method returns an array with two contents: the filename where the summaryTable file is saved, and the string with the summaryTable
        $filename = $arrayResponse["filename"];
        $summaryTable = $arrayResponse["summaryTable"];
        $arraySummaryTitles = $arrayResponse["summaryTitles"];

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
            'specie' => $specie,
            'summaryTable' => $summaryTable,
            'filenameSummaryTable' => $filename,
            'arraySummaryTitles' => $arraySummaryTitles,
        ));

    }
}
