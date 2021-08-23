$(document).ready(() => {

    let gFile = function() { getFiles().then(deleteFilesEvent);}   
     
    hasSession().then(gFile).catch( function() {
        login().then(gFile)
    });

    $("#urls").keyup(urlKeyup);
    $("#urls").focusout(validateUrls);

    $("#submit").click(() => {
        if($("#urls").val().length > 0) {
            downloadRequest().then(function() {
                $("#submit").attr('disabled', false);
                getFiles().then(deleteFilesEvent);
            });

            $("#submit").attr('disabled', true);
            $("#urls").val('');
        }
    });
});

function removeFile(filename) {
    let promise = new Promise((resolve, reject) => {
        $.post('../functions/mirrorlinkAjax.php', { removeFile: true, name : filename}
        ).done(function (response) {
            let json = JSON.parse(response);
            if (json.status == 1) {
                resolve();
            } else {
                reject();
            }
        });
    });

    return promise;
}

function hasSession() {
    let promise = new Promise((resolve, reject) => {
        $.post('../functions/mirrorlinkAjax.php', {checkSession : true} 
        ).done(function(response) {
            let json = JSON.parse(response);
            if(json.status == 1)
                resolve();
            else
                reject();
        });
    });

    return promise;
}

function deleteFilesEvent() {
    $(".delete-file").click(function() {
        removeFile($(this).attr('aria-label')).then(function() {
            getFiles().then(deleteFilesEvent)
        });
    });
}

function urlKeyup(event) {
    let disp    = $('#charCount');
    let input   = $(this);
    let chars   = input.val().length;
    let lines   = (input.val().match(/[^\n]+/g) || []).length;
    let rows    = (input.val().match(/\n/g)     || []).length;

    input.attr('rows', rows + 2);
    disp.html(`${chars} ch<br> ${lines} ln`);

    if(event.key == "Enter") 
        validateUrls();
}

function validateUrls() {
    let input   = $("#urls");
    let exp     = /^((https?:\/\/)?(w{3}\.)?\w{2,256}\.[a-z]{2,8}[-a-zA-Z0-9()@:%_\+.~#?&//=\[\]]* *\n?|\n)*$/;
    if(input.val().trim().length == 0 || input.val().match(exp)) {       
        input.removeClass("is-invalid");        
    } else {
        input.addClass("is-invalid");
    }
}

function login() {
    let pass = prompt("Enter your password:");
    let promise = new Promise((resolve, reject) => {
        $.post('../functions/mirrorlinkAjax.php', { pass: pass }
        ).done(function (response) {
            let json = JSON.parse(response);
            if (json.status == 1) {
                setMesssage("Logged in successfully.", "alert-success");
                resolve();
            } else {            
                setMesssage("login failed.", "alert-warnning");
                reject();
            }
        });
    });

    return promise;    
}

function downloadRequest() {
    let promise = new Promise((resolve, reject) => {
        
        const urls              = $('#urls').val().split('\n');
        const progressContainer = $('#download-process');
        let progresses          = [];
        let index               = 0;
        
        if($('#urls').hasClass('is-invalid')) {
            reject();
            return;
        }

        for(let url of urls) {
            const progress = $('<div class="ring-loader mx-1"><div></div><div></div><div></div><div></div></div>');
            progressContainer.append(progress);
            progresses.push(progress);
            try {
                $.post('../functions/mirrorlinkAjax.php', { url: url, idx : index++}
                ).done(function(response) {
                    let idx     = parseInt(this.data.match(/(?<=idx=)\d/)[0]);
                    let json    = JSON.parse(response);
    
                    if (json.status == 1) {
                        getFiles();
                        progresses[idx].addClass('ok');
                    } else {
                        progresses[idx].addClass('fail');                
                    }

                    setTimeout(() => {progresses[idx].remove()}, 1000);
                    if(--index == 0)
                        resolve();
                });
            } catch {
                reject();
            }
        }
    });

    return promise;
}

function getUnvarifiedList() {
    $.post('../functions/mirrorlinkAjax.php', { getUnvarifiedList: true }
    ).done(function (response) {
        let json = JSON.parse(response);
        if (json.status == 1) {
            setMesssage("Registred successfully.", "alert-success");            
        } else {
            setMesssage("Failed to register.", "alert-danger");
        }
    });
}

function register() {
    let username = $("#regUsername").val();
    let password = $("#regPassword").val();
    $.post('../functions/mirrorlinkAjax.php', { username: username, password : password}
    ).done(function (response) {
        let json = JSON.parse(response);
        if (json.status == 1) {
            
        }
    });
}

function getFiles() {
    let promise = new Promise((resolve, reject) => {

        $.post('../functions/mirrorlinkAjax.php', { getFiles: true }
        ).done(function (response) {
            const json = JSON.parse(response);
            let progressContainer   = $("#progress-container");
            let linksContainer      = $("#links-container");
            
            progressContainer.html("");
            linksContainer.html("");
            
            let id          = 1;
            let fileSize    = 0;
            let usedSpace   = 0;
            
            if (json.status == 1) {
                for(const file of json.files) {
                    fileSize = (file.size/1048576).toFixed(1);
                    usedSpace += file.size;

                    // append progress bar
                    progressContainer.append(`<div class='progress-bar text-black-50' 
                        style='width: ${(file.size/json.maxSpace * 100).toFixed(1)}%;
                        background-color: hsl(${(id*45)% 255}, 100%, 80%);'>
                        ${fileSize > 280? fileSize : '' }</div>`);

                    // append to file list
                    linksContainer.append(currentLinksRowGenerator(id, file.name, file.size));
                    id++;
                }
                
                $("#remained-size").html((usedSpace/1048576).toFixed(1) + '/' + (json.maxSpace/1048576).toFixed(1) + ' Mb');
                resolve();
            }
            else {
                reject();
            }
        });
    });    

    return promise;
}

function currentLinksRowGenerator(id, name, size) {
    return `<div class='row mt-1 rounded bg-light'>
            <div class='col-sm-1 rounded-top rounded-sm-left text-secondary' style='background-color: hsl(${(id*45) % 255}, 100%, 95%);'>
                ${id}</div><div class='col-sm-7' title='${name}'>
                <a class='text-secondary text-decoration-none' href='download/${name}'>${name.slice(name.indexOf('-')+1)}</a>
            </div>
            <div class='col-sm-4 text-right small'>
            ${(size > 1048576? size/1048576 : size/1024).toFixed(1)} 
            ${size > 1048576? 'Mb' : 'Kb'}
            <i class="bi bi-trash delete-file" title="remove file" aria-label="${name}"></i>
            </div></div>`;
}

function setMesssage(message, className) {
    let messageCont = $('#message');

    messageCont.fadeIn();
    messageCont.html(message);
    messageCont.addClass(className);
    
    setTimeout(() => {
        messageCont.fadeOut();
        messageCont.fadeOut();
        messageCont.removeClass(className);
    }, 4000);
}