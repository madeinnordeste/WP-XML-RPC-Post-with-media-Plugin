Example to use:

	include 'IXR_Library.php';
	$client->debug = true;
	$data["title"] = 'My Post Title'
	$data["content"] = 'My Post Content text';
	$data["author"] = 1;
	$data["categories"] = array(6,7);
	$data['custom_fields'] = array('first_custom_field' => 'first',
							'second_custom_field' => 'second',
							'custom_date' => date('Y-m-d H:i:s'));
								
	$data["medias"] = array('http://www.domain.com/1.jpg',
							'http://www.domain.com/3.gif',
							'http://www.domain.com/3.png');
	
	$args = array('username', 'password', $data);
	
	$client = new IXR_Client('http://www.you_wordpress_blog.com/xmlrpc.php');
	
	if (!$client->query('postWithMedia', $args)) {
	    die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
	}else{
	    echo "Article Posted Successfully";
	}

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/madeinnordeste/wp-xml-rpc-post-with-media-plugin/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

