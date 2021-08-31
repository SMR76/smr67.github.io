JSON.isValid = function(str) {
    try { JSON.parse(str); } 
    catch (e) { return false; }
    return true;
}

$(document).ready(function() {
     var mirrorlink = new mirorlinkAjax();
});

class mirorlinkAjax {
    constructor () {
        mirorlinkAjax.hasSession();
        mirorlinkAjax.registerEvents();
    }

    static registerEvents() {
        $("#urls").keyup(mirorlinkAjax.urlKeyup);
        $("#urls").focusout(mirorlinkAjax.validateUrls);

        $("#submit").click(mirorlinkAjax.submitFile);
        $("#register").click(mirorlinkAjax.register);

        $("#urls").attr('disabled',true);
        $("#submit").attr('disabled',true);

        $("#login").click(mirorlinkAjax.login);
    }

    static submitFile() {
        if($("#urls").val().length > 0) {
            mirorlinkAjax.downloadRequest();
            $("#submit").attr('disabled', true);
            $("#urls").val('');
        }
    }
    
    static removeFile(filename) {
        $.post('../functions/mirrorlinkAjax.php', { removeFile: true, name : filename})
            .done(function (response) {
                    let json = JSON.parse(response);
                    if (json.status == 1) {
                        mirorlinkAjax.updateFileList();
                        mirorlinkAjax.setMesssage("File removed successfully")
                    } 
                });
    }

    static hasSession() {
        $.post('../functions/mirrorlinkAjax.php', {checkSession : true})
            .done(function(response) {
                    let json = JSON.parse(response);
                    if(json.status == 1) {
                        mirorlinkAjax.updateFileList();
                        mirorlinkAjax.updateUnvarifiedList();
                        $("#urls").attr('disabled',false);
                        $("#submit").attr('disabled',false);
                    }
                    else {
                        $("#login-modal").modal('show');
                    }
                });
    }

    static login() {
        let password = $("#loginPassword").val();
        let username = $("#loginUsername").val();

        $.post('../functions/mirrorlinkAjax.php', { password: password, username : username })
            .done(function (response) {
                    let json = JSON.parse(response);
                    if (json.status == 1) {
                        mirorlinkAjax.setMesssage("Logged in successfully.", "alert-success");
                        mirorlinkAjax.updateFileList();
                        mirorlinkAjax.updateUnvarifiedList();
                        
                        $("#urls").attr('disabled',false);
                        $("#submit").attr('disabled',false);
                    } else {
                        mirorlinkAjax.setMesssage("login failed.", "alert-danger");
                        setTimeout( () => $("#login-modal").modal('show') , 500);
                    }
                });
    }
    
    static urlKeyup(event) {
        let disp    = $('#charCount');
        let input   = $(this);
        let chars   = input.val().length;
        let lines   = (input.val().match(/[^\n]+/g) || []).length;
        let rows    = (input.val().match(/\n/g)     || []).length;

        input.attr('rows', rows + 2);
        disp.html(`${chars} ch<br> ${lines} ln`);

        if(event.key == "Enter") {
            mirorlinkAjax.validateUrls();
        }
    }

    static downloadRequest() {        
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

            $.post('../functions/mirrorlinkAjax.php', { url: url, idx : index++}).
                done(function(response) {
                        let idx     = parseInt(this.data.match(/(?<=idx=)\d/)[0] || 0);                        
                        let json    = "";
                        if(JSON.isValid(response)) {
                            json = JSON.parse(response);
                        } 
                        else {
                            mirorlinkAjax.setMesssage(response,"alert-danger", 100000);
                        }
        
                        if (json.status == 1) {
                            progresses[idx].addClass('ok');
                            mirorlinkAjax.updateFileList();
                        } else {
                            progresses[idx].addClass('fail');
                        }
                        
                        setTimeout(() => progresses[idx].remove(), 1000);
                        $("#submit").attr('disabled', false);
                    });
        }
    }

    static validateUrls() {
        let input   = $("#urls");
        let exp     = /^((https?:\/\/)?(w{3}\.)?\w{2,256}\.[a-z]{2,8}[-a-zA-Z0-9()@:%_\+.~#?&//=\[\]]* *\n?|\n)*$/;
        if(input.val().trim().length == 0 || input.val().match(exp)) {       
            input.removeClass("is-invalid");        
        } else {
            input.addClass("is-invalid");
        }
    }

    static updateUnvarifiedList() {    
        $.post('../functions/mirrorlinkAjax.php', { getUnvarifiedList: true })
            .done(function (response) {
                    let json = JSON.parse(response);
                    if (json.status == 1) {
                        let usersContainer  = $("#users-container");
                        let rowColor        = false;

                        usersContainer.html('');

                        for(const user of json.unvarifiedList) {
                            usersContainer.append(mirorlinkAjax.unvarifiedUsersRowGenerator(user.id, user.username, rowColor));
                            rowColor        = !rowColor;
                        }

                        $(".accept-user").click(function(){ mirorlinkAjax.userAction('accept', $(this).attr('aria-label')); } );
                        $(".reject-user").click(function(){ mirorlinkAjax.userAction('reject', $(this).attr('aria-label')); } );
                    }
                });
    }

    static userAction(action, id) {
        $.post('../functions/mirrorlinkAjax.php', { useraction: action, userId : id })
            .done(function (response) {
                    let json = JSON.parse(response);
                    if (json.status == 1) {
                        mirorlinkAjax.updateUnvarifiedList();
                    }
                });
    }

    static register() {
        let username = $("#regUsername").val();
        let password = $("#regPassword").val();
    
        $("#regUsername").val('');
        $("#regPassword").val('');
    
        $.post('../functions/mirrorlinkAjax.php', { newUsername: username, newPassword : password})
            .done(function (response) {
                    let json = JSON.parse(response);
                    if (json.status == 1) {
                        mirorlinkAjax.setMesssage("Registred successfully.", "alert-success");
                        mirorlinkAjax.updateUnvarifiedList();           
                    } else {
                        mirorlinkAjax.setMesssage("Failed to register.", "alert-danger");
                    }
                });
    }

    static updateFileList() {
        $.post('../functions/mirrorlinkAjax.php', { updateFileList: true })
            .done(function (response) {
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
                            linksContainer.append(mirorlinkAjax.currentLinksRowGenerator(id, file.name, file.size));
                            id++;
                        }
                        
                        $("#remained-size").html((usedSpace/1048576).toFixed(1) + '/' + (json.maxSpace/1048576).toFixed(1) + ' Mb');
                        $(".delete-file").click(function() { mirorlinkAjax.removeFile($(this).attr('aria-label')); });
                    }
                });
    }

    static currentLinksRowGenerator(id, name, size) {
        return `<div class='row mt-1 rounded bg-light'>
                <div class='col-sm-1 rounded-top rounded-sm-left text-secondary' style='background-color: hsl(${(id*45) % 255}, 100%, 95%);'>
                ${id}</div><div class='col-sm-7' title='${name}'>
                <a class='text-secondary text-decoration-none' href='download/${name}' target="_blank">${name.slice(name.indexOf('-')+1)}</a></div>
                <div class='col-sm-4 text-right small'>
                ${(size > 1048576? size/1048576 : size/1024).toFixed(1)} 
                ${size > 1048576? 'Mb' : 'Kb'}
                <i class="bi bi-trash delete-file" title="remove file" aria-label="${name}"></i>
                </div></div>`;
    }

    static unvarifiedUsersRowGenerator(id, username, rowColor) {
        return `<div class='row mt-1 rounded ${rowColor ? 'bg-light' : 'bg-secondary text-light'}'>
                <div class='col-sm-1'>${id}</div>
                <div class='col-sm-7'>${username}</div>
                <div class='col-sm-4 text-center text-sm-right small mb-2'>
                <i aria-label="${id}" title="reject" class="reject-user bi bi-trash"></i>
                <i aria-label="${id}" title="accept" class="accept-user bi bi-bookmark-check"></i>
                </div></div>`;
    }
    
    static setMesssage(message, className, delay = 4000) {
        let messageCont = $('#message');
    
        messageCont.fadeIn();
        messageCont.html(message);
        messageCont.addClass(className);
        
        setTimeout(function() {
                        messageCont.fadeOut();
                        messageCont.removeClass(className);
                    }, delay);
    }
}





