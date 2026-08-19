let fileMap = new Map();

let coverQueue = [];
let contentQueue = [];

let isUploadingCover = false;
let isUploadingContent = false;

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

    console.log(BAData.covers);
    console.log(BAData.contents);

    BAData.covers.forEach(c => {
        let coverList = document.getElementById("ba_cover_list");
        let data = getUploadedData(c);
        let element = createPDFElement(data);
        coverList.appendChild(element);
    });

    BAData.contents.forEach(c => { 
        let contentList = document.getElementById("ba_content_list");
        let data = getUploadedData(c);
        let element = createPDFElement(data);
        contentList.appendChild(element);

        addFile(data);
    });
});

function randomId() {
    return Math.random().toString(36).slice(2, 10);
}

// General
function getUploadedData(data) {
    return {
        id: data.id,
        file: null,
        name: data.name,
        pages: data.pages,
        width: data.width,
        height: data.height,
        state: 'uploaded'
    }
}

function getDefaultFileData(file) {
    return {
        id: randomId(),
        file: file,
        name: file.name,
        pages: null,
        width: null,
        height: null,
        state: 'pending'
    }
}


function addFile(data)
{
    fileMap.set(data.id, data);
}

function getFileData(id)
{
    return fileMap.get(id);
}

function updateFileId(oldId, newId)
{
    let data = fileMap.get(oldId);
    if (!data) 
        return;

    data.id = newId;

    fileMap.delete(oldId);
    fileMap.set(newId, data);
}

function removeFile(id)
{
    fileMap.delete(id);
}

// Covers
function uploadCovers(files) {
    let coverList = document.getElementById("ba_cover_list");

    files.forEach(file => {
        let data = getDefaultFileData(file);

        coverQueue.push(data);
        let element = createPDFElement(data);
        coverList.appendChild(element);
    });

    if (!isUploadingCover) {
        uploadCoverHandler();
    }
}

function uploadCoverHandler() {
    if (coverQueue.length === 0) {
        isUploadingCover = false;
        console.log('All uploads done');
        return;
    }

    isUploadingCover = true;
    const data = coverQueue.shift();
    uploadCover(data);
}



// Content
function uploadContents(files) {
    let contentList = document.getElementById("ba_content_list");

    files.forEach(file => {
        let data = getDefaultFileData(file);

        contentQueue.push(data);
        let element = createPDFElement(data);
        contentList.appendChild(element);
    });

    if (!isUploadingContent) {
        uploadContentHandler();
    }
}

function uploadContentHandler() {
    if (contentQueue.length === 0) {
        isUploadingContent = false;
        console.log('All uploads done');
        return;
    }

    isUploadingContent = true;
    const data = contentQueue.shift();
    uploadContent(data);
}


// Uploads
function uploadCover(data) {
    uploadPDF(data, "cover", uploadCoverHandler);
}

function uploadContent(data) {
    uploadPDF(data, "content", uploadContentHandler);
}

function uploadPDF(data, type, uploadCallback) {
    const file = data.file;
    console.log(`Uploading: ${file.name}`);
    updateState(data, "uploading");

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
            updateState(data, "failed");
        } 
        else {
            data.pages = res.data.pages;
            data.width = res.data.width;
            data.height = res.data.height;
            updateStateAndId(data, "success", res.data.id);
        }
        uploadCallback();
    })
    .catch(err => {
        console.error(`Error on: ${file.name}`, err);
        updateState(data, "failed");
        uploadCallback();
    });
}

function deleteElement(id)
{
    document.getElementById("ba_pdf_" + id).remove();
}

function deletePDF(id, name)
{
    // placeholder data
    let data = getFileData(id);

    console.log(`Deleting: ${name}`);
    updateState(data, "deleting");

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
            updateState(data, "failed");
        } 
        else {
            document.getElementById("ba_pdf_" + id).remove();
            removeFile(id);
        }
    })
    .catch(err => {
        console.error(`Error on: ${name}`, err);
        updateState(data, "failed");
    });
}

function updateState(data, state)
{
    const oldElement = document.getElementById("ba_pdf_" + data.id);
    data.state = state;
    if (!oldElement)
        return;

    oldElement.replaceWith(createPDFElement(data));
}

function updateStateAndId(data, state, id)
{
    const oldElement = document.getElementById("ba_pdf_" + data.id);
    data.state = state;
    data.id = id;
    if (!oldElement)
        return;

    oldElement.replaceWith(createPDFElement(data));
}

function createPDFElement(data)
{
    const div = document.createElement('div');

    div.innerHTML = `
        <div id="ba_pdf_${data.id}" class="inner-card ba-p-2"> 
            <p class="ba-m-0 ba-text-overflow"></p> 
            <div class="ba-flex-row ba-flex ba-space-between ba-align-center"> 
                <div class="ba-flex-row ba-gap-2 ba-pdf-meta"></div>
                <button class="ba-m-0 ba-tag-remove">Remove</button> 
            </div> 
        </div>`;

    const element = div.children[0];

    element.querySelector('.ba-text-overflow').textContent = data.name;

    const meta = element.querySelector('.ba-pdf-meta');

    const addTag = (text) => {
        const span = document.createElement('span');
        span.className = 'ba-tag';
        span.textContent = text;
        meta.appendChild(span);
    };

    addTag(data.state);

    if (data.width && data.height) {
        addTag(`${data.width} × ${data.height}`);
    }

    if (data.pages) {
        addTag(`${data.pages} page${data.pages === 1 ? '' : 's'}`);
    }

    element.querySelector('button').addEventListener('click', () => {
        deletePDF(data.id, data.name);
    });

    return element;
}