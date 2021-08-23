$(document).ready(() => {
    repositories();
    coffee();
    googleAnalytic();
    events();
});

var country = "unknown";
async function coffee() {
    try {
        await $.get("https://ipwhois.app/json/").then((data) => {
            country = data.country;
        });
    } catch(e) {
        //error
    }
    let coffeeElement = $("#donate");
    
    if(country === "Iran") {
        coffeeElement.attr('href','https://idpay.ir/s-m-r');
        coffeeElement.attr('target','_blank');
    }
    else {
        let text = `Donate to my <b class="text-warning">Bitcoin Cash</b><br><small>click to copy!</small>`;
        let tt = coffeeElement.children('p');      
        tt.html(text);        
        
        coffeeElement.mouseout(()=>{
            tt.html(text);
        });
    }
}

async function repositories() {
    let url = "functions/repositoryAjax.php";
    let rpositoriesContainer = $("#repositories");

    let oddCol = 0;
    let cols = "" , rows = "";

    await $.get(url).then((repositories) => {
        repositories = JSON.parse(repositories);

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
}

function repositoryCellHtml(repository) {
    let forked = repository.forked == 1? '<sub class="text-primary">forked</sub>' : '';

    return `<div class="container-fluid repo-container pt-2">
            <div class="row"><div class="col-12 col-xl-6">
            <h6>${repository.name}</h6> ${forked}
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

    // handle donate click
    $("#donate").on('click', function() {
        if(country !== "Iran") {
            let tt = $(this).children('p');
            tt.html(`<i class="text-light"> Copied!</i>`);

            let bitcoincashAddress = "bitcoincash:qrnwtxsk79kv6mt2hv8zdxy3phkqpkmcxgjzqktwa3";
            copyToClipboard(bitcoincashAddress);
        }
    });

    $("#status").on('click',async function() {
        let token = prompt("Enter the Token:", "");

        if(token) {
            changeStatus("loading", true);

            await $.get("cronjob/updateRepositoryData.php?token="+token).then((data) => {
                let json = JSON.parse(data);
                if(json.status == 1) { 
                    changeStatus("success"); 
                    setTimeout(() => { location.reload(); }, 6000);
                }
                else {
                    changeStatus("error");
                }
            });
            
            setTimeout(changeStatus, 4500, "loading");
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

function isTouchDevice() {
    return (('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0));
}