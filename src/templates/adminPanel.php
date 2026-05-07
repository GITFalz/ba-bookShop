<?php
    $helper = new BA_Helper(BA_BOOKSHOP_HELPER);

    $clientID = $helper->get_or_update_post("clientID", "");
    $secret = $helper->get_or_update_post("secret", "");
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