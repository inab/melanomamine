<?php

namespace Melanomamine\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;



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

    public function searchKeywordAction($keyword)
    {
        $message="inside searchKeywordAction";
        //ld($keyword);
        $paginator = $this->get('ideup.simple_paginator');

        $elasticaQueryString  = new \Elastica\Query\QueryString();
        //'And' or 'Or' default : 'Or'
        $elasticaQueryString->setDefaultOperator('AND');
        $elasticaQueryString->setQuery($keyword);

        // Create the actual search object with some data.
        $elasticaQuery  = new \Elastica\Query();

        //Order by. Uncomment once the score has been added to the elasticsearch server
        $elasticaQuery->setSort(array('melanoma_score' => array('order' => 'desc')));
        $elasticaQuery->setQuery($elasticaQueryString);
        #ldd($elasticaQuery);
        //$elasticaQuery->setSize($this->container->getParameter('melanomamine.total_documents_elasticsearch_retrieval'));
        $elasticaQuery->setSize(1500);
        $abstractsInfo = $this->container->get('fos_elastica.index.melanomamine.abstracts');/** To get resultSet to get values for summary**/
        $resultSetAbstracts = $abstractsInfo->search($elasticaQuery);
        //ld($resultSetAbstracts[0]);
        $arrayAbstracts=$resultSetAbstracts->getResults();
        $arrayResultsAbs = $paginator
            //->setMaxPagerItems($this->container->getParameter('etoxMicrome.number_of_pages'), 'abstracts')
            ->setMaxPagerItems(15, 'abstracts')
            //->setItemsPerPage($this->container->getParameter('etoxMicrome.evidences_per_page'), 'abstracts')
            ->setItemsPerPage(10, 'abstracts')
            ->paginate($arrayAbstracts,'abstracts')
            ->getResult()
        ;
        $hitsShowed=count($arrayAbstracts);
        ############### Uncomment when a SCORE has been added to the elasticsearch entries
        //$meanScore=$this->getMmmrScore($resultSetAbstracts, 'score', 'mean');
        //$medianScore=$this->getMmmrScore($resultSetAbstracts, $orderBy, 'median');
        //$rangeScore=$this->getMmmrScore($resultSetAbstracts, $orderBy, 'range');
        //$finderDoc=false;
        ############### Comment when a SCORE has been added to the elasticsearch entries
        $meanScore=0;
        $medianScore=0;
        $rangeScore=0;

        $resultSetDocuments = array();
        $arrayResultsDoc = array();

        $entityName=$keyword;

        return $this->render('MelanomamineFrontendBundle:Search:results.html.twig', array(
            'keyword' => $keyword,
            'arrayResultsAbs' => $arrayResultsAbs,
            'arrayResultsDoc' => $arrayResultsDoc,
            'resultSetAbstracts' => $resultSetAbstracts,
            'resultSetDocuments' => $resultSetDocuments,
            'entityName' => $entityName,
            'orderBy' => "score",
            'hitsShowed' => $hitsShowed,
            'meanScore' => $meanScore,
            'medianScore' => $medianScore,
            'rangeScore' => $rangeScore,
        ));


    }
}
