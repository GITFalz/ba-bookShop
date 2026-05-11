<?php
    $helper = new BA_Helper(BA_BOOKSHOP_HELPER);

    $clientID = $helper->get_or_update_post_encrypted("clientID", "");
    $secret = $helper->get_or_update_post_encrypted("secret", "");
?>
<div>
    <form method="post">
        <label>Client ID</label>
        <input name="clientID" value="<?=$clientID?>">
        <label>Secret</label>
        <input name="secret" value="<?=$secret?>">
        <button type="submit">Save</button>
    </form>
</div>
<?php

function ba_get_order_data()
{
    $item1 = array(
        'productId' => 'boek_hc_a5_sta',
        'pageCount' => 32,
        'quantity' => 1,
        'files' => array(                     
            'cover' => 'https://www.printapi.nl/sample-book-a5-cover.pdf',
            'content' => 'https://www.printapi.nl/sample-book-a5-content.pdf'
        )
    );      

    $address = array(
        'name' => 'John Doe',
        'line1' => 'Osloweg 75',
        'postCode' => '9700 GE',
        'city' => 'Groningen',
        'country' => 'NL'
    );

    return array(
        'email' => 'bjornarvalkea@gmail.com',
        'items' => array($item1),                 
        'shipping' => array(
            'address' => $address
        )
    );
}