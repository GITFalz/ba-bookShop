jQuery(document).ready(function($) 
{
    document.getElementById('ba_cover_list_upload').addEventListener('change', function (e) {
        const files = Array.from(e.target.files);
        uploadCovers(files);
    });

    document.getElementById('ba_content_list_upload').addEventListener('change', function (e) {
        const files = Array.from(e.target.files);
        uploadContents(files);
    });
});

function randomId() {
    return Math.random().toString(36).slice(2, 10);
}

let covers = [];
let contents = [];

let isUploadingCover = false;
let isUploadingContent = false;


// Covers
function uploadCovers(files) {
    let coverList = document.getElementById("ba_cover_list");

    files.forEach(file => {
        let data = {
            id: randomId(),
            file: file,
            name: file.name
        };

        covers.push(data);
        let element = generateFile(data.id, data.name, "pending");
        coverList.appendChild(element);
    });

    if (!isUploadingCover) {
        uploadCoverHandler();
    }
}

function uploadCoverHandler() {
    if (covers.length === 0) {
        isUploadingCover = false;
        console.log('All uploads done');
        return;
    }

    isUploadingCover = true;
    const data = covers.shift();
    uploadCover(data);
}

function uploadCover(data) {
    uploadPDF(data, "cover", uploadCoverHandler);
}


// Content
function uploadContents(files) {
    let contentList = document.getElementById("ba_content_list");

    files.forEach(file => {
        let data = {
            id: randomId(),
            file: file,
            name: file.name
        };

        contents.push(data);
        let element = generateFile(data.id, data.name, "pending");
        contentList.appendChild(element);
    });

    if (!isUploadingContent) {
        uploadContentHandler();
    }
}

function uploadContentHandler() {
    if (contents.length === 0) {
        isUploadingContent = false;
        console.log('All uploads done');
        return;
    }

    isUploadingContent = true;
    const data = contents.shift();
    uploadContent(data);
}

function uploadContent(data) {
    uploadPDF(data, "content", uploadContentHandler);
}



// Global functions
function uploadPDF(data, type, uploadCallback) {
    const file = data.file;
    console.log(`Uploading: ${file.name}`);
    replaceElement(data.id, data.name, "uploading");

    const formData = new FormData();
    formData.append('action', 'ba_upload_' + type);
    formData.append('ba_' + type, file);
    formData.append('ba_nonce', BAData.nonce);

    fetch(BAData.ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            console.warn(`Failed: ${file.name}`, res.data);
            replaceElement(data.id, data.name, "fail");
        } 
        else {
            replaceElement(data.id, data.name, "success");
            data.id = res.data.id;
        }
        uploadCallback();
    })
    .catch(err => {
        console.error(`Error on: ${file.name}`, err);
        replaceElement(data.id, data.name, "fail");
        uploadCallback();
    });
}

function deleteElement(id)
{
    document.getElementById("ba_pdf_" + id).remove();
}

function deletePDF(id, name)
{
    console.log(`Deleting: ${name}`);
    replaceElement(id, name, "deleting");

    const formData = new FormData();
    formData.append('action', 'ba_delete_pdf');
    formData.append('ba_id', id);
    formData.append('ba_nonce', BAData.nonce);

    fetch(BAData.ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            console.warn(`Failed: ${name}`, res.data);
            replaceElement(id, name, "fail");
        } 
        else {
            document.getElementById("ba_pdf_" + id).remove();
        }
    })
    .catch(err => {
        console.error(`Error on: ${name}`, err);
        replaceElement(id, name, "fail");
    });
}

function replaceElement(id, name, state)
{
    let oldElement = document.getElementById("ba_pdf_" + id);
    let newElement = generateFile(id, name, state);
    oldElement.replaceWith(newElement);
}

function generateFile(id, name, state)
{
    let element = document.createElement('div');
    element.id = "ba_pdf_" + id;
    element.classList.add("inner-card");
    element.classList.add("ba-p-2");
    element.innerHTML = `
        <p class="ba-m-0 ba-text-overflow">${name}</p>
        <div class="ba-flex-row ba-flex ba-space-between">
            ${getState(id, name, state)}
        </div>
    `;
    return element;
}

function getState(id, name, state)
{
    if (state == "pending")
    {
        return `
            <p class="ba-m-0">Pending</p>
        `;
    }
    else if (state == "uploading")
    {
        return `
            <p class="ba-m-0">Uploading...</p>
        `;
    }
    else if (state == "fail-delete")
    {
        return `
            <p class="ba-m-0">Deleting...</p>
            <button class="ba-m-0" onclick="deletePDF(${id}, '${name}')">Remove</button>
        `;
    }
    else if (state == "fail")
    {
        return `
            <p class="ba-m-0">Failed</p>
            <button class="ba-m-0" onclick="deleteElement(${id})">Remove</button>
        `;
    }
    else if (state == "deleting")
    {
        return `
            <p class="ba-m-0">Deleting...</p>
        `;
    }
    // default uploaded for now is fine, will do error maybe later
    return `
        <p class="ba-m-0">Uploaded</p>
        <button class="ba-m-0" onclick="deletePDF(${id}, '${name}')">Remove</button>
    `;
}