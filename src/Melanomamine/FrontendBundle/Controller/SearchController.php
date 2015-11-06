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
        ));

    }
}
