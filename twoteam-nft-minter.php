<?php
/*
 * Plugin Name: twoteam-nft-minter
 * Description: Creates NFTs from a list of picture paths and descriptions, and adds them as WooCommerce products
 * Version: 1.0
 
 I cannot determine whether there are any syntax errors or issues with the logic in the code you have provided. However, there are a few things to note:

    The code uses external dependencies such as Web3 and Automattic\WooCommerce\Client. Ensure that these dependencies are correctly installed and loaded before executing the code.

    The code uses hardcoded values for various parameters such as the Infura project ID, NFT contract address, NFT contract ABI, consumer key, and consumer secret. Ensure that these values are updated with your own values before executing the code.

    The code creates a WooCommerce product for each NFT, but the product is created with a fixed price of 1. If you want to use a different pricing strategy, you will need to modify the code accordingly.

    The code creates a log file to store information about the NFTs created. The file is created in the plugin directory, so ensure that the plugin directory is writable before executing the code.

    The code defines two functions, display_log() and nft_shortcode(), but these functions are not called anywhere in the code. If you want to use these functions, you will need to add code to call them.
 
 */
require_once __DIR__ . '/vendor/autoload.php';
use Web3\Web3;
use Automattic\WooCommerce\Client;
use Web3\Contract;
use Web3\Utils;

// Read the list of picture paths and descriptions from a text file
$file_path = plugin_dir_path(__FILE__) . 'nft_list.txt';
$nft_list = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Set up the Ethereum network parameters
$rpc_endpoint = 'https://rinkeby.infura.io/v3/your_infura_project_id';
$nft_contract_address = '0x1234567890123456789012345678901234567890';
$nft_contract_abi = '0x1234567890123456789012345678901234567890...'; // truncated for brevity
$nft_contract_private_key = '0x1234567890123456789012345678901234567890123456...'; // your private key here

// Set up the WooCommerce API parameters
$woocommerce_endpoint = 'https://testplugin.templeofaskur.com/wp-json/wc/v3';
$woocommerce_key = 'ck_6566c49515967c3fa6ac0181153c0df58604ba62';
$woocommerce_secret = 'cs_755f1a0a9bf67d896aec11806957470ecfb2efdf';
$woocommerce_client = new Client($woocommerce_endpoint, $woocommerce_key, $woocommerce_secret);

// Create the web3 instance
$web3 = new Web3($rpc_endpoint);

// Define the create_nft function
function create_nft($picture_path, $description, $nft_contract_address, $nft_contract_abi, $nft_contract_private_key, $rpc_endpoint)
{
    // Create the NFT
    $token_id = null;
    $web3 = new Web3($rpc_endpoint);
    $contract = new Contract($web3->provider, $nft_contract_abi);
    $contract->at($nft_contract_address)->send('createToken', $picture_path, $description, function ($err, $tx) use (&$token_id) {
        if ($err !== null) {
            echo 'Error creating NFT: ' . $err->getMessage();
            return;
        }
        $token_id = $tx;
    }, ['from' => $nft_contract_private_key]);

    // Mint the NFT
    $contract->at($nft_contract_address)->send('mint', $token_id, function ($err, $tx) use ($token_id, $nft_contract_private_key, $log_file, $picture_path) {
        if ($err !== null) {
            echo 'Error minting NFT: ' . $err->getMessage();
            return;
        }
        // Log the token ID and description
        $log_file = plugin_dir_path(__FILE__) . 'nft_log.txt';
        file_put_contents($log_file, "$token_id: $description\n", FILE_APPEND | LOCK_EX);
        // Delete the picture file
        unlink($picture_path);
    }, ['from' => $nft_contract_private_key]);

    return $token_id;
}

// Loop over the NFT list and create each NFT
foreach ($nft_list as $nft_data) {
    // Parse the picture path and description from the list
    list($picture_path, $description) = explode('|', $nft_data);

    // Create the NFT
    $token_id = create_nft($picture_path, $description, $nft_contract_address, $nft_contract_abi, $nft_contract_private_key, $rpc_endpoint);
    if ($token_id) {
        echo "Created NFT with token ID $token_id\n";

        // Delete the picture
        if (file_exists($picture_path)) {
            unlink($picture_path);
            echo "Deleted picture $picture_path\n";
        }

        // Create a WooCommerce product for the NFT
        $product = new WC_Product();
        $product->set_name($description);
        $product->set_regular_price('1');
        $product->set_virtual(true);
        $product->set_featured(true);
        $product->set_catalog_visibility('hidden');
        $product->set_description('This is an NFT created using the 2TeamNFT-Minter Plugin.');
        $product->save();
    }
}

// Define the display_log function
function display_log()
{
    $log_file = plugin_dir_path(__FILE__) . 'nft_log.txt';
    $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($log_lines as $log_line) {
        echo $log_line . '<br>';
    }
}

// Define the nft_shortcode function
function nft_shortcode($atts = [])
{
    $atts = shortcode_atts([
        'token_id' => '',
    ], $atts);

    $nft_contract_address = '0x1234567890123456789012345678901234567890';
    $nft_contract_abi = '0x1234567890123456789012345678901234567890...'; // truncated for brevity
    $rpc_endpoint = 'https://rinkeby.infura.io/v3/your_infura_project_id';

    $web3 = new Web3($rpc_endpoint);
    $contract = new Contract($web3->provider, $nft_contract_abi);
    $contract->at($nft_contract_address)->call('getToken', $atts['token_id'], function ($err, $token) {
        if ($err !== null) {
            echo 'Error getting NFT: ' . $err->getMessage();
            return;
        }
        // Display the NFT image and description
        echo "<img src='$token[0]' alt='NFT image'>";
        echo "<p>$token[1]</p>";
    });

}

add_shortcode('nft', 'nft_shortcode');



/*This code defines the display_log function, which reads the log file and displays the token IDs and descriptions of all created NFTs. It also defines the nft_shortcode function, which is a WordPress shortcode that can be used to display the image and description of a specific NFT, given its token ID. The shortcode calls the getToken method of the NFT contract to retrieve the image and description, and then displays them using HTML.*/
