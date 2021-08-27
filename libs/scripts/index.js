$(document).ready(() => {
    
    repositories().then(function () {
        // open link in touch devices with double tap.
        if('ontouchstart' in window) {
            let hoverableElements = $("#contactMe a:not(#donate), repo-container");

            hoverableElements.click(function(e) {
                if(this.tap) {
                    this.tap = false;
                } else {
                    this.tap = true;
                    setTimeout((x) => x.tap = false, 500, this); 
                    e.preventDefault();
                }
            });

            $('[class$="tooltiptext"]').append("<br><small>(double tap to open)</small>");
        }
    });

    coffee();
    googleAnalytic();
    events();
});

async function coffee() {    
    var country = "";
    try {
        $.get("https://ipwhois.app/json/objects=country").then(function(data) {
            country = data.country;
        }).then(function() {    
            
            let coffeeElement = $("#donate");
            let tooltip = $("#donate p");
            
            if(country === "Iran") {
                coffeeElement.attr('href','https://idpay.ir/s-m-r');
                coffeeElement.attr('target','_blank');
            }
            else {
                let text = `Donate to my <b class="text-warning">Bitcoin Cash</b><br><small>click to copy!</small>`;
                tooltip.html(text);
                coffeeElement.mouseout(function() { tooltip.html(text);});
            }

            let copyWalletAddr = function() {
                if(country !== "Iran") {
                    $("#donate p").html(`<i class="text-light"> Copied!</i>`);
                    let bitcoincashAddress = "bitcoincash:qrnwtxsk79kv6mt2hv8zdxy3phkqpkmcxgjzqktwa3";
                    copyToClipboard(bitcoincashAddress);
                }
            }

            if('ontouchstart' in window) {
                coffeeElement.click(function(e) {
                    if(this.tap == true) { copyWalletAddr(); }
                    else { this.tap = true; }
                    e.preventDefault();
                });

                coffeeElement.mouseleave(function() {this.tap = false});
            }
            else {
                coffeeElement.click(copyWalletAddr);
            }
        });
    } catch {
        //error
    }
}

async function repositories() {
    let url = "functions/repositoryAjax.php";
    let rpositoriesContainer = $("#repositories");
    let oddCol = 0, cols = "";

    let promise = $.get(url).then((response) => {
        let repositories = "";

        try { repositories = JSON.parse(response);}
        catch { return; } // if failed to parse response terminate function 

        for (const repository of repositories) {
            cols += `<div class="col-12 col-md-6">${repositoryCellHtml(repository)}</div>`;
            oddCol = (oddCol+1)%2;
            if(oddCol == 0) {
                rpositoriesContainer.append(`<div class="row">${cols}</div>`);
                cols = "";
            }
        }
        rpositoriesContainer.append(`<div class="row">${cols}</div>`);
    });

    return promise;
}

function repositoryCellHtml(repository) {
    let forked  = repository.forked == 1? '<sub class="text-primary">forked</sub>' : '';
    let repoUrl = repository.mainBranchUrl.slice(0,repository.mainBranchUrl.indexOf('/commits'));

    return `<div class="container-fluid repo-container pt-2">
            <div class="row"><div class="col-12 col-xl-6">
            <h6><a href="${repoUrl}" class="text-dark text-decoration-none" target="_blank">${repository.name}</a></h6> ${forked}
            <span class="badge badge-dark ">${repository.lastTagName}</span>
            <p class="text-muted small">${repository.description || "no description"}</p>
            </div><div class="col-12 col-xl-6">
            ${commitRoadHtml(repository.lastCommits, repository.mainBranchUrl)}
            </div></div></div>`;
}

function commitRoadHtml(lastCommits, mainBranchUrl) {
    let nodes = [];
    for(const commit of lastCommits) {
        nodes.push(`<a class="commit-node" href="${commit.url}">
                    <p>${commit.id}</p><p class="ttext">
                    ${commit.author_name}<br/>
                    ${commit.date}<br/>
                    ${commit.message}</p></a>`);
    }

    nodes.reverse();

    return `<div class="commit-container"><div class="commit-road">
            <a class="dots" target="_blank" href="${mainBranchUrl}">
            ${lastCommits[0].id > 3  ? '<div class="dot"></div>'.repeat(3) : ''}
            ${nodes.join("")}</a></div></div>`;
}

function events() {
    // navbar scroll spy
    $("#navbarNavBrand,#navbarNav a").on('click', function (event) {
        if (this.hash !== "") {
            event.preventDefault();
            let hash = this.hash;

            $('html, body').animate({
                scrollTop: $(hash).offset().top - 80
            }, 800, function () {
                if (history.pushState) {
                    history.pushState(null, null, hash);
                }
                else {
                    location.hash = hash;
                }
            });
        }
    });

    $("#status").on('click',async function() {
        let token = prompt("Enter the Token:", "");

        if(token) {
            changeStatus("loading", true);

            $.get("cronjob/updateRepositoryData.php?token="+token).then((data) => {
                let json = JSON.parse(data);
                if(json.status == 1) { 
                    changeStatus("success"); 
                    setTimeout(() => { location.reload(); }, 6000);
                }
                else {
                    changeStatus("error");
                }
            }).then(() => {setTimeout(changeStatus, 4500, "loading");});
        }
    });

    setTimeout(() => {
        let message = $("#welcomeMessage");
        message.slideUp();
    },15000);
}

function changeStatus(tagName, active = false) {
    $("#status").children().removeClass("on");
    $("#" + tagName).addClass("on");
    
    if(active && tagName == "loading")
        $("#" + tagName).addClass("active");
    else if(tagName == "loading")
        $("#" + tagName).removeClass("active");
}

function copyToClipboard(text) {
    let $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();
}

function googleAnalytic() {
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-XL9HMP5PK3');
}
