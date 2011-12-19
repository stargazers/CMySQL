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
      @brief Class for MySQL databases

      @author Aleksi Räsänen
              aleksi_rasanen@hotmail.com
              2008
  */
  // ***********************************************  
  class CMySQL
  {
    //! This will keep information if we are connected or not
    private $_isConnected = false;

    //! Last executed query
    private $_lastQuery = '';

    //! Results of last query
    private $_lastResult = '';

    //! Database connection
    private $_connection = '';

    //! Last executed INSERT INTO -query insertion ID
    private $lastInsertID = '';

    
    //***************************************************  
    //  __construct
    /*!
	@brief Class constructor. If we give a file as a
	       parameter, then we read configuration from
	       that file and connect database directly.

	@param $settingsFile Database settings. All settings
	       must be in associative array $db where must
	       exists keys server, username, password, port
	       and database.

        @return Database connection
    */
    //***************************************************  
    public function __construct( $settingsFile = '' )
    {
      // If config file is given and it exists, try to connect
      if( $settingsFile != '' && file_exists( $settingsFile ) )
      {
        include $settingsFile;

        $this->_connection = $this->connect( $db['server'] ,
            $db['username'], $db['password'], $db['port'], 
            $db['database'] );

        // Return created connection
        return $this->_connection;
      }

    }

    // ***********************************************
    //  query
    /*!
        @brief Execute query

        @param $query SQL query

        @return Last query results. On error we throw
                an Exception.
    */
    // ***********************************************
    public function query( $query )
    {
      // Execute query only if we have connected
      if( $this->_isConnected )
      {
	// Save last query to class variable
        $this->_lastQuery = $query;
        
        // Execute query
        $ret = @mysql_query( $query, $this->_connection );

        // If there were error, throw Exception
        if( mysql_error() != '' )
          throw new Exception( 'MySQL error: ' . mysql_error() );

	// Save query results to class variable
        $this->_lastResult = $ret;

	// Get last insert ID
        $this->lastInsertID = mysql_insert_id();

        // Return last query resultset
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
	@brief Set connection. This can be used if
	       we want to use already existing 
	       database connection instead of creating
	       new connection.

        @param $connection Database connection handle
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
        @brief Return handle of a connection

        @return Connection handle
    */
    // ***********************************************
    public function getConnection()
    {
      return $this->_connection;
    }


    // ***********************************************
    //  disconnect
    /*!
        @brief Disconnects connection to database server
    */
    // ***********************************************
    public function disconnect()
    {
      // Try to disconnect only if conncetion is open
      if( $this->_isConnected )
      {
        $ret = @mysql_close();

        // If connection closed fine, update status
        if( $ret )
          $this->_isConnected = false;
      }
      else
      {
        throw new Exception( 'Can\'t disconnect because \
          you are not connected!' );
      }
    }


    // ***********************************************
    //  getLastQuery
    /*!
        @brief Return last executed query

        @return Last executed query in string
    */
    // ***********************************************
    public function getLastQuery()
    {
      return $this->_lastQuery;
    }


    // ***********************************************
    //  getLastResult
    /*!
        @brief Returns last resultset

        @return Results of last query in resultset
    */
    // ***********************************************
    public function getLastResult()
    {
      return $this->_lastResult;
    }


    // ***********************************************
    //  fetchAssoc
    /*!
        @brief Create associative array from resultset

        @param $db_ret Resultset

        @return Associative array
    */
    // ***********************************************
    public function fetchAssoc( $db_ret )
    {
      // Get number of rows
      $numRows = @mysql_num_rows( $db_ret );

      // Here we store results
      $assocArray = array();

      // Create assoc array
      for( $i=0; $i < $numRows; $i++ )
      {
        // Split row to assoc array
        $row = mysql_fetch_assoc( $db_ret );

        // Add row to assoc array
        $assocArray[] = $row;
      }

      // Return assoc array
      return $assocArray;

    }


    // ***********************************************
    //  connect
    /*!
        @brief Connect to database server

        @param $server Database server

        @param $username Database username

        @param $password Database password

        @param [$port] Database port number

        @param [$database] Database to select

        @return Connection. If failed, throws an Exception.
    */
    // ***********************************************
    public function connect( $server, $username, $password, $port = ''
      , $database = '' )
    {
      if( $port == '' )
        $ret = @mysql_connect( $server, $username, $password );
      else
        $ret = @mysql_connect( $server, $username, $password, $port );

      // If connection failed, throw new Exception
      if(! $ret )
      {
        throw new Exception( 'Failed to connect database server! Error: ' 
			. mysql_error() );
      }
      else
      {
        // Save status so we can know that we are connected
        $this->_isConnected = true;

        // Select database if param $database was given
        if( $database != '' )
          $this->selectDatabase( $database );

        // Save database connection
        $this->_connection = $ret;
      }

      // Return created cnnection
      return $ret;
    }


    // ***********************************************
    //  selectDatabase
    /*!
        @brief Select database to use

        @param $database Database what we want to use
    */
    // ***********************************************
    public function selectDatabase( $database )
    {
      $ret = @mysql_select_db( $database );

      // If selection failed, throw new Exception
      if(! $ret )
      {
        throw new Exception( 'Failed to select database!' );
      }

    }


    // ***********************************************
    //  isConnected
    /*!
        @brief Tells if we are connected or not

        @return True if connected, otherwise false
    */
    // ***********************************************  
    public function isConnected()
    {
      return $this->_isConnected;
    }

    
    // ***********************************************
    //  numRows
    /*!
        @brief Get number of rows in resultset

	@param [$db_ret] Query results where we want
	       to read number of rows. If this is not
	       given, then we use local variable
	       where is stored last query results.
	       If that is not found, then we throw
	       an Exception.

        @return Number of rows
    */
    // ***********************************************  
    public function numRows( $db_ret = '' )
    {
      if( $db_ret != '' )
      {
        return @mysql_num_rows( $db_ret );
      }
      else
      {
	// If we have done query with this class,
	// use last results.
        if( $this->_lastResult != '' )
          return @mysql_num_rows( $this->_lastResult );

        // No queries done - Throw an Exception.
        else
          throw new Exception( 'Give query return as a parameter!' );
      }
    }


    // ***********************************************
    //  getLastInsertID
    /*!
        @brief Return last ID value of last INSERT
	       INTO-query.
        
        @return ID-number. 0 if we have not created
	        any INSERT INTO -queries with this
		database connection.
    */
    // ***********************************************
    public function getLastInsertID()
    {
      return $this->lastInsertID;
    }

	// ************************************************** 
	//  queryAndAssoc
	/*!
		@brief Creates a SQL query and try to fetch
		  results in the assoc array.
		@param $query SQL Query
		@return Array of values. If no rows, we return -1,
		  if query failed, we return -2. Use 'query'
		  to get more information about failed queries.
	*/
	// ************************************************** 
	public function queryAndAssoc( $query )
	{
		try
		{
			$ret = $this->query( $query );

			if( $this->numRows( $ret ) > 0 )
				return $this->fetchAssoc( $ret );

			return -1;
		}
		catch( Exception $e )
		{
			return -2;
		}
	}
  }

?>
