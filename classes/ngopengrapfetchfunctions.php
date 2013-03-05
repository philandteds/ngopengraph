<?php

class ngOpenGraphFetchFunctions
{
	public function fetchOGTags( $url ) {
		// CURL is used to follow redirections and change timeout
		$ch  = curl_init();
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ); 
		$response = curl_exec( $ch );
		curl_close( $ch );

		preg_match_all(
			'|<meta[^>]+property="og:([^"]*)"[^>]+content="([^"]*)"[^>]+>|i',
			$response,
			$out,
			PREG_PATTERN_ORDER
		);

		$tags = array();
		foreach( $out[1] as $key => $tag ) {
			$value = $out[2][ $key ];
			if( isset( $tags[ $tag ] ) ) {
				if( is_array( $tags[ $tag ] ) === false ) {
					$tags[ $tag ] = array( $tags[ $tag ], $value );
				} else {
					$tags[ $tag ][] = $value;
				}
			} else {
				$tags[ $tag ] = $value;
			}
		}

		return array( 'result' => $tags );
	}
}
