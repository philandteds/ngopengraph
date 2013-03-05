<?php

class OpenGraphOperator
{
    function OpenGraphOperator()
    {
        $this->Operators = array( 'opengraph', 'language_code' );
    }

    function &operatorList()
    {
        return $this->Operators;
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
       return array( 'opengraph' => array( 'nodeid' => array( 'type' => 'integer',
                                                                'required' => true,
                                                                'default' => 0 ) ),
					'language_code' => array()
	   	);
    }

    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace,
                     &$currentNamespace, &$operatorValue, &$namedParameters )
    {
       switch ( $operatorName )
       {
			case 'opengraph':
			{
				$operatorValue = $this->generateOpenGraphTags( $namedParameters['nodeid'] );
				break;
			}
			case 'language_code':
			{
				$operatorValue = eZLocale::instance()->httpLocaleCode();
				break;
			}
       }
    }

	function generateOpenGraphTags( $nodeID )
	{
		$returnArray = array();

		$ogIni = eZINI::instance( 'ngopengraph.ini' );
		$facebookCompatible = $ogIni->variable( 'General', 'FacebookCompatible' );
		$availableClasses = $ogIni->variable( 'General', 'Classes' );

		$contentNode = eZContentObjectTreeNode::fetch($nodeID);

		if(!($contentNode instanceof eZContentObjectTreeNode))
		{
			return array();
		}

		$contentObject = $contentNode->object();

		if(!($contentObject instanceof eZContentObject) || !in_array($contentObject->contentClassIdentifier(), $availableClasses))
		{
			return array();
		}

		$returnArray = $this->processGenericData($contentNode, $ogIni, $facebookCompatible, $returnArray);

		$returnArray = $this->processObject($contentObject, $ogIni, $returnArray);

		if($this->checkRequirements($returnArray, $facebookCompatible))
			return $returnArray;
		else
		{
			eZDebug::writeDebug( 'No','Facebook Compatible?' );
			return array();
		}
	}

	function processGenericData($contentNode, $ogIni, $facebookCompatible, $returnArray)
	{
		$siteName = eZINI::instance()->variable( 'SiteSettings', 'SiteName' );
		if(strlen(trim($siteName)) > 0)
			$returnArray['og:site_name'] = trim($siteName);

		$urlAlias = $contentNode->urlAlias();
		eZURI::transformURI($urlAlias, false, 'full');
		$returnArray['og:url'] = $urlAlias;

		if($facebookCompatible == 'true')
		{
			$appID = $ogIni->variable( 'GenericData', 'app_id' );
			if(strlen(trim($appID)) > 0)
				$returnArray['fb:app_id'] = trim($appID);

			$defaultAdmin = $ogIni->variable( 'GenericData', 'default_admin' );
			$data = '';
			if(strlen(trim($defaultAdmin)) > 0)
			{
				$data = trim($defaultAdmin);

				$admins = $ogIni->variable( 'GenericData', 'admins' );

				if(count($admins) > 0)
				{
					$admins = trim(implode(',', $admins));
					$data = $data . ',' . $admins;
				}
			}

			if(strlen($data) > 0)
				$returnArray['fb:admins'] = $data;
		}

		return $returnArray;
	}

	function processObject($contentObject, $ogIni, $returnArray)
	{
		if ( $ogIni->hasVariable( $contentObject->contentClassIdentifier(), 'LiteralMap' ) )
		{
			$literalValues = $ogIni->variable( $contentObject->contentClassIdentifier(), 'LiteralMap' );
			eZDebug::writeDebug($literalValues, 'LiteralMap');

			if ( $literalValues )
			{
				foreach($literalValues as $key => $value)
				{
					if(strlen($value) > 0)
						$returnArray[$key] = $value;
				}
			}
		}

		if ( $ogIni->hasVariable( $contentObject->contentClassIdentifier(), 'AttributeMap' ) )
		{
			$attributeValues = $ogIni->variableArray( $contentObject->contentClassIdentifier(), 'AttributeMap' );
			eZDebug::writeDebug($attributeValues, 'AttributeMap');

			if ( $attributeValues )
			{
				foreach( $attributeValues as $key => $value ) {
					$attributeIdentifiers = explode( ',', $value[0] );
					$dataMember = isset( $value[1] ) ? $value[1] : false;

					$contentObjectAttributeArray = $contentObject->fetchAttributesByIdentifier( $attributeIdentifiers );
					if( is_array( $contentObjectAttributeArray ) === false ) {
						continue;
					}
					$contentObjectAttributeArray = array_values($contentObjectAttributeArray);

					foreach( $contentObjectAttributeArray as $contentObjectAttribute ) {
						if( $contentObjectAttribute instanceof eZContentObjectAttribute === false ) {
							continue;
						}

						$openGraphHandler = ngOpenGraphBase::getInstance( $contentObjectAttribute );
						if( $dataMember ) {
							$data = $openGraphHandler->getDataMember( $dataMember );
						} else {
							$data = $openGraphHandler->getData();
						}

						if(
							is_array( $data )
							|| strlen( $data ) > 0
						) {
							$returnArray[ $key ] = $data;
							break;
						}
					}
				}
			}
		}

		return $returnArray;
	}

	function checkRequirements($returnArray, $facebookCompatible)
	{
		$arrayKeys = array_keys($returnArray);

		if(!in_array('og:title', $arrayKeys) || !in_array('og:type', $arrayKeys) ||
			!in_array('og:image', $arrayKeys) || !in_array('og:url', $arrayKeys))
		{
			eZDebug::writeError($arrayKeys, 'Missing an OG required field: title, image, type, or url');
			return false;
		}

		if($facebookCompatible == 'true')
		{
			if (!in_array('og:site_name', $arrayKeys) || (!in_array('fb:app_id', $arrayKeys) && !in_array('fb:admins', $arrayKeys)))
			{
				eZDebug::writeError($arrayKeys, 'Missing a FB required field (in ngopengraph.ini): app_id, DefaultAdmin, or Sitename (site.ini)');
				return false;
			}
		}

		return true;
	}
}

?>