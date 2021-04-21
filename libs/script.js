$(document).ready(() => {

    initializeRepositories();
    coffeeFunc();
    initEvents();
    googleAnalytic();
});

var cntry = "unknown";
async function coffeeFunc() {
    try {
        await $.get("https://ipwhois.app/json/").then((data) => {
            cntry = data.country;
        });
    } catch(e) {
        //error
    }
    let coffee = $("#donate");
    
    if(cntry === "Iran") {
        coffee.attr('href','https://idpay.ir/s-m-r');
        coffee.attr('target','_blank');
    }
    else {
        let text = `Donate to my <b class="text-warning">Bitcoin Cash</b><br><small>click to copy!</small>`;
        let tt = coffee.children('p');      
        tt.html(text);        
        
        coffee.mouseout(()=>{
            tt.html(text);
        });
    }
}

async function initializeRepositories() {
    let url = "https://api.github.com/users/smr76/repos";
    await $.get(url).then((data) => {
        for (const repoInfo of data) {
            repoAppender(repoInfo);
            break;
        }
    });
}

async function repoAppender(repoInfo) {
    let rposContainer = $("#repositories");
    let tag = "";
    let commit_url = repoInfo.commits_url.substr(0,repoInfo.commits_url.length-6);
    let commits_road = "";

    try { 
        await $.get(repoInfo.tags_url).then((data)=>{            
        if(data[0] !== undefined)
            tag = data[data.length-1].name;
        });
    }
    catch(e) {

    }
    await $.get(commit_url).then((data)=>{
        let cnum = data.length;
        let index = 0;
        if(data[0] !== undefined) {
            
            for(const x of data) {
                if(index ++ > 6 || cnum <= 0)
                    break;
                commits_road =                
                    `<a class="commit-node" href="${x.html_url}">
                    <p>${cnum--}</p>
                    <p class="ttext">
                    ${x.commit.author.name}<br/>
                    ${x.commit.author.date}<br/>
                    ${x.commit.message}</p>
                    </a>` + commits_road;
            }

            commits_road =
                `<div class="commit-container"><a class="dots"></a>
                <div class="commit-road">` 
                + commits_road +
                `</div>${cnum >= 1  ? '<div class="dots"></div>' : ''}</div>`;
        }
    });
    
    var repoFormat =
        `<div class="row pt-1 pb-1 rounded"><div class="col-12 col-md-6 small text-left">
        <a class="alert no-text-deco text-dark text-capitalize" href="${repoInfo.html_url}" target="_blank">
        ${repoInfo.name}
        ${repoInfo.fork == true? '<sub class="text-primary">forked</sub>' : ''}</a>
        <span  class="badge badge-dark">${tag}</span>
        </div><div class="col-12 pt-2 pt-md-0 col-md-6 small text-muted text-left">
        ${repoInfo.description != null? repoInfo.description : "no description."}
        </div><div class="col-12 pt-2 pt-md-0 small" style="height:55px;">
        ${commits_road}
        </div></div></div>`;
    
    rposContainer.append(repoFormat);   
}

function initEvents() {
    // bookmark button
    $('#bookmarkMe').click(() => {
        if (window.sidebar) { // Mozilla Firefox Bookmark
            window.sidebar.addPanel(location.href,document.title,"");
            return true;
        } else if(window.external) { // IE Favorite
            window.external.AddFavorite(location.href,document.title); 
            return true;
        }
        else if(window.opera && window.print) { // Opera Hotlist
            var elem = document.createElement('a');
            elem.setAttribute('href', url);
            elem.setAttribute('title', title);
            elem.setAttribute('rel', 'sidebar');
            elem.click(); //this.title=document.title;
            return true;
        }
    });

    // navbar scroll spy
    $("#navbarNavBrand,#navbarNav a").on('click', function (event) {
        if (this.hash !== "") {
            event.preventDefault();
            var hash = this.hash;

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

    setTimeout(() => {
        let message = $("#welcomeMessage");
        message.slideUp();
    },15000);

    // handle donate click
    $("#donate").on('click', function() {
        if(cntry !== "Iran") {
            let tt = $(this).children('p');
            tt.html(`<i class="text-light"> Copied!</i>`);

            let bitcoincashAddress = "bitcoincash:qrnwtxsk79kv6mt2hv8zdxy3phkqpkmcxgjzqktwa3";
            copyToClipboard(bitcoincashAddress);
        }
    });
}

function copyToClipboard(text) {
    var $temp = $("<input>");
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