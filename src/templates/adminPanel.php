<?php
    $helper = new BA_Helper(BA_BOOKSHOP_HELPER);

    $clientID = $helper->get_or_update_post_encrypted("clientID", "");
    $secret = $helper->get_or_update_post_encrypted("secret", "");
    $type = $helper->get_or_update_post("environment", "test");
?>
<div class="ba-p-6 ba-flex-col ba-gap-4">
    <div class="wide-card ba-p-2">
        <form method="post"class="ba-m-0">
            <div class="ba-flex-col">
                <div class="ba-p-2">
                    <label>Client ID</label>
                    <input name="clientID" value="<?=$clientID?>">
                </div>
                <div class="ba-p-2">
                    <label>Secret</label>
                    <input name="secret" value="<?=$secret?>">
                </div>
                <div class="ba-p-2">
                    <label>Environment</label>
                    <select name="environment">
                        <option <?= $type === "test" ? "selected" : "" ?>>
                            test
                        </option>
                        <option <?= $type === "live" ? "selected" : "" ?>>
                            live
                        </option>
                    </select>
                </div>
                
                <div class="ba-p-2">
                    <button type="submit">Save</button>
                </div>
            </div>
        </form>
    </div>
    <div class="wide-card ba-p-2 ba-flex-col ba-gap-2">
        <div class="ba-flex ba-center ba-py-2">
            <h1 class="ba-m-0">
                Covers
            </h1>
        </div>
        <div class="ba-separator"></div>
        <div id="ba_cover_list" class="ba-flex-col ba-gap-2">
            <?php foreach (ba_get_covers() as $cover): ?>
                <div id="ba_pdf_<?=$cover["id"]?>" class="inner-card ba-p-2">
                    <p class="ba-m-0 ba-text-overflow"><?= $cover["name"] ?></p>
                    <div class="ba-flex-row ba-flex ba-space-between">
                        <p class="ba-m-0">Uploaded</p>
                        <button class="ba-m-0" onclick="deletePDF(<?=$cover['id']?>, '<?=$cover['name']?>')">Remove</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="ba-separator"></div>
        <div class="inner-card ba-center ba-flex-col ba-py-6">
            <input type="file" id="ba_cover_list_upload" accept=".pdf" multiple>
        </div>
    </div>
    <div class="wide-card ba-p-2 ba-flex-col ba-gap-2">
        <div class="ba-flex ba-center ba-py-2">
            <h1 class="ba-m-0">
                Content
            </h1>
        </div>
        <div class="ba-separator"></div>
        <div id="ba_content_list" class="ba-flex-col ba-gap-2">
            <?php foreach (ba_get_contents() as $cover): ?>
                <div id="ba_pdf_<?=$cover["id"]?>" class="inner-card ba-p-2">
                    <p class="ba-m-0 ba-text-overflow"><?= $cover["name"] ?></p>
                    <div class="ba-flex-row ba-flex ba-space-between">
                        <p class="ba-m-0">Uploaded</p>
                        <button class="ba-m-0" onclick="deletePDF(<?=$cover['id']?>, '<?=$cover['name']?>')">Remove</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="ba-separator"></div>
        <div class="inner-card ba-center ba-flex-col ba-py-6">
            <input type="file" id="ba_content_list_upload" accept=".pdf" multiple>
        </div>
    </div>
</div>
<?php