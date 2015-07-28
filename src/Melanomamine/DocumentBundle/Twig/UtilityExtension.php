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
            new \Twig_SimpleFilter('getScoreToShow', array($this, 'getScoreToShowFilter')),
            new \Twig_SimpleFilter('colorCodingScore', array($this, 'colorCodingScoreFilter')),
        );
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