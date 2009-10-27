<?php

	/* 
	Class for using MySQL database with PHP.
	Copyright (C) 2008, 2009 Aleksi Räsänen <aleksi.rasanen@runosydan.net>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	*/


  // ***********************************************  
  //  CMySQL
  /*!
      @brief MySQL-luokan käsittelyyn tarkoitettu luokka

      @author Aleksi Räsänen
              aleksi_rasanen@hotmail.com
              2008
  */
  // ***********************************************  
  class CMySQL
  {
    //! Tällä pidetään tietoa siitä onko yhteys kantaan auki vaiko ei
    private $_isConnected = false;

    //! Viimeisin kysely tallennetaan tähän
    private $_lastQuery = '';

    //! Viimeisimmän kyselyn tulokset tallennetaan tähän
    private $_lastResult = '';

    //! Yhteys 
    private $_connection = '';

    //! Viimeksi suoritetun INSERT-kyselyn palauttama ID
    private $lastInsertID = '';

    
    //***************************************************  
    //  __construct
    /*!
        @brief Luokan konstruktori. Jos annetaan parametrinä
               tiedosto, luetaan tietokannan asetukset
               siitä ja yhdistetään tietokantaan.

        @param $settingsFile Tietokannan asetukset. Asetukset pitää
               olla assosiatiivisessa taulukossa $db, jossa on
               keyt server, username, password, port ja database.

        @return Tietokantayhteys
    */
    //***************************************************  
    public function __construct( $settingsFile = '' )
    {
      // Jos asetustiedosto ollaan annettu ja se on olemassa,
      // luetaan asetukset siitä ja yhdistetään tietokantaan.
      if( $settingsFile != '' && file_exists( $settingsFile ) )
      {
        include $settingsFile;

        $this->_connection = $this->connect( $db['server'] ,
            $db['username'], $db['password'], $db['port'], 
            $db['database'] );

        // Palautetaan tietokantayhteys
        return $this->_connection;
      }

    }

    // ***********************************************
    //  query
    /*!
        @brief Suorittaa SQL-kyselyn

        @param $query Kysely joka halutaan suorittaa

        @return Viimeisimmän haun tulokset. Jos tulee virhe,
                heitetään Exception.
    */
    // ***********************************************
    public function query( $query )
    {
      // Yritetään suorittaa kysely vain jos on yhdistetty
      if( $this->_isConnected )
      {
        // Tallennetaan viimeisin kysely luokan sisäiseen muuttujaan
        $this->_lastQuery = $query;
        
        // Suoritetaan kysely
        $ret = @mysql_query( $query, $this->_connection );

        // Jos jotain meni pieleen, heitetään exception
        if( mysql_error() != '' )
          throw new Exception( 'MySQL error: ' . mysql_error() );

        // Tallennetaan kyselyn tulos luokan sisäiseen muuttujaan
        $this->_lastResult = $ret;

        // Otetaan talteen viimeisimmästä INSERT-kyselystä saatu ID
        $this->lastInsertID = mysql_insert_id();

        // Palautetaan haun tulokset
        return $ret;

      }
      else
      {
        throw new Exception( 'You are not connected to database!' );
      }
    }
    

    // ***********************************************
    //  setConnection
    /*!
        @brief Asettaa luokan tietokantayhteyden. Tätä
               voidaan käyttää, jos ei haluta luoda
               uutta yhteyttä, vaan käyttää vanhaa.

        @param $connection Yhteys
    */
    // ***********************************************
    public function setConnection( $connection )
    {
      $this->_isConnected = true;
      $this->_connection = $connection;
    }


    // ***********************************************
    //  getConnection
    /*!
        @brief Palauttaa tietokantayhteyden resurssin

        @return Tietokantayhteys
    */
    // ***********************************************
    public function getConnection()
    {
      return $this->_connection;
    }


    // ***********************************************
    //  disconnect
    /*!
        @brief Katkaisee yhteyden tietokantapalvelimeen
    */
    // ***********************************************
    public function disconnect()
    {
      // Yritetään katkaista yhteys vain jos se on avoin
      if( $this->_isConnected )
      {
        $ret = @mysql_close();

        // Jos yhteyden katkaisu onnistui, päivitetään muuttujan tila
        if( $ret )
          $this->_isConnected = false;
      }

      // Ei oltu yhdistetty
      else
      {
        throw new Exception( 'Can\'t disconnect because \
          you are not connected!' );
      }
    }


    // ***********************************************
    //  getLastQuery
    /*!
        @brief Palauttaa viimeksi suoritetun SQL-kyselyn

        @return Merkkijonona viimeisimmän kyselyn
    */
    // ***********************************************
    public function getLastQuery()
    {
      return $this->_lastQuery;
    }


    // ***********************************************
    //  getLastResult
    /*!
        @brief Palauttaa viimeksi suoritetun kyselyn tulokset

        @return MySQL resurssina viimeisimmän kyselyn tulokset
    */
    // ***********************************************
    public function getLastResult()
    {
      return $this->_lastResult;
    }


    // ***********************************************
    //  fetchAssoc
    /*!
        @brief Tällä tehdään haun tuloksista assosiatiivitaulukko

        @param $db_ret query-funktion antama paluuarvo

        @return Assosiatiivisen taulukon
    */
    // ***********************************************
    public function fetchAssoc( $db_ret )
    {
      // Lasketaan rivien määrä montako on tullut kyselyssä
      $numRows = @mysql_num_rows( $db_ret );

      // Tähän taulukkoon tallennetaan paluuarvo
      $assocArray = array();

      // Luodaan haun tuloksista assosiatiivinen taulukko
      for( $i=0; $i < $numRows; $i++ )
      {
        // Pilkotaan rivi assosiatiivitaulukoksi
        $row = mysql_fetch_assoc( $db_ret );

        // ...ja lisätään se lopulliseen taulukkoon
        $assocArray[] = $row;
      }

      // Palautetaan assosiatiivitaulukko
      return $assocArray;

    }


    // ***********************************************
    //  connect
    /*!
        @brief Yhdistää tietokantapalvelimeen

        @param $server Palvelin johon yhdistetään

        @param $username Tietokannan käyttäjätunnus

        @param $password Tietokannan käyttäjän salasana

        @param [$port] Portti jota käytetään yhdistäessä

        @param [$database] Kanta joka valitaan yhdistämisen jälkeen

        @return Yhteys. Epäonnistuessaan heittää Exceptionin.
    */
    // ***********************************************
    public function connect( $server, $username, $password, $port = ''
      , $database = '' )
    {

      // Jos halutaan yhdistää ilman porttia
      if( $port == '' )
        $ret = @mysql_connect( $server, $username, $password );

      // Jos halutaan yhdistää portin kanssa
      else
        $ret = @mysql_connect( $server, $username, $password, $port );

      // Jos yhdistäminen epäonnistui, heitetään exception
      if(! $ret )
      {
        throw new Exception( 'Failed to connect database server!' );
      }

      // Jos onnistuttiin yhdistää kantaan
      else
      {
        // Kerrotaan että yhdistäminen on onnistunut
        $this->_isConnected = true;

        // Jos haluttiin valita tietokanta samalla
        if( $database != '' )
          $this->selectDatabase( $database );

        // Pidetään yhteys tallessa
        $this->_connection = $ret;
      }

      // Palautetaan muodostettu yhteys
      return $ret;
    }


    // ***********************************************
    //  selectDatabase
    /*!
        @brief Valitsee käytettävän tietokannan

        @param $database Tietokanta joka valitaan
    */
    // ***********************************************
    public function selectDatabase( $database )
    {
      $ret = @mysql_select_db( $database );

      // Jos tietokannan valinta epäonnistui
      if(! $ret )
      {
        throw new Exception( 'Failed to select database!' );
      }

    }


    // ***********************************************
    //  isConnected
    /*!
        @brief Palauttaa tiedon onko yhdistettynä kantaan vaiko ei

        @return True jos on yhdistetty, false jos ei.
    */
    // ***********************************************  
    public function isConnected()
    {
      return $this->_isConnected;
    }

    
    // ***********************************************
    //  numRows
    /*!
        @brief Lukee kyselyn rivien määrän

        @param [$db_ret] Kyselyn tulokset josta halutaan rivimäärä
               Jos tätä ei olla annettu, luetaan rivimäärä viimeksi
               suoritetusta kyselyn tuloksesta jos sellainen löytyy.
               Jos ei, heitetään exception. 

        @return Rivimäärä
    */
    // ***********************************************  
    public function numRows( $db_ret = '' )
    {
      // Jos parametrinä ollaan saatu kyselyn tulos, luetaan sen rivimäärä
      if( $db_ret != '' )
      {
        return @mysql_num_rows( $db_ret );
      }

      /* Jos ei olla parametrinä saatu kyselyn tulosta, katsotaan onko
         tehty jokin kysely jo tällä luokalla ja luetaan viimeisimmästä
         hakutuloksesta montako riviä löytyi. */
      else
      {
        // Jos on tehty kysely tällä luokalla
        if( $this->_lastResult != '' )
          return @mysql_num_rows( $this->_lastResult );

        // Ei mistään mistä lukea rivimääärää - heitetään exception
        else
          throw new Exception( 'Give query return as a parameter!' );
      }
    }


    // ***********************************************
    //  getLastInsertID
    /*!
        @brief Palauttaa viimeisimmän INSERT-kyselyn 
            antaman ID-arvon.
        
        @return ID-numeron. 0, jos ei olla tehty tällä
            yhteydellä yhtään INSERT-kyselyä.
    */
    // ***********************************************
    public function getLastInsertID()
    {
      return $this->lastInsertID;
    }
  }

?>
