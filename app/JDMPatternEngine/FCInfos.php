<?php

namespace App\JDMPatternEngine;

/**
 * Classe comportementale pour le backward chaining FC
 * Ceci est une implémentation par défaut.
 * Le but est d'utiliser l'héritage de classes pour redéfinir les comportements que l'on veut obtenir
 */
class FCInfos
{
    /**
     * Profondeur courante
     * @var int
     */
    public $depth = 0;

    /**
     * Nombre total d'appels récursifs
     * @var type 
     */
    public $calls = 1;

    /**
     * Nombre de résultats obtenus à la profondeur 0
     * @var type 
     */
    public $nbResults = 0;

    /**
     * Demande de chargement de données à partir de $on (un terme)
     * Les données doivent être stockées vers la DB locale du FC
     * 
     * @param Term $on
     * @return null|boolean
     */
    public function moreData($on)
    {
        return null;
    }

    /**
     * Sélection (filtrage) des domaines
     * 
     * @param iterable $domain Le domaine
     * @return iterable Le domaine filtré
     */
    public function selectDomain($domain)
    {
        return $domain;
    }

    /**
     * Peut on itérer le calcul de la boucle principale ?
     * @return boolean
     */
    public function canIterate()
    {
        return true;
    }

    /**
     * Peut-on faire une récursion dans le chainage arrière ?
     * @return boolean
     */
    public function canDoRecursion()
    {
        return true;
    }

    /**
     * Calcule le poids de la conclusion d'une règle
     * 
     * @param Rule $ruleBinded
     * @return int
     */
    public function computeWeight($ruleBinded)
    {
        return 0;
    }

    /**
     * Combien de résultats maximum à retourner pour une requête ?
     * @return int
     */
    public function getNbMaxResults()
    {
        return 10;
    }

    /**
     * Filtre un résultat
     * @param array $res Le résultat
     * @return boolean
     */
    public function filterOneResult($res)
    {
        return true;
    }
}