$(document).ready(() => {

    initializeRepositories();
    coffeeFunc();
    googleAnalytic();
    initEvents();
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

var addedRepos = 0;
var totalRepos = 0;

async function initializeRepositories() {
    let url = "https://api.github.com/users/smr76/repos";
    await $.get(url).then((data) => {
        totalRepos = data.length;
        for (const repoInfo of data) {
            repoAppender(repoInfo);
        }
    });
}

async function repoAppender(repoInfo) {
    let rposContainer = $("#repositories");
    let tag = "";
    let commit_url = repoInfo.commits_url.substr(0,repoInfo.commits_url.length-6);
    let git_main_commits_url = 'https://github.com/' + rposContainer + '/commits/main';
    let commits_road = "";
    let cnum = 0;

    try { 
        await $.get(repoInfo.tags_url).then((data)=>{            
        if(data[0] !== undefined)
            tag = data[data.length-1].name;
        });
    }
    catch(e) {
        //in case of handling errors
    }

    await $.ajax({
        type: 'GET',
        url: commit_url + '?per_page=1',
        success: 
        function(d, ts, r){
            cnum = r.getResponseHeader('link').match(/\d+(?=>;)/g)[1];
    }});

    await $.get(commit_url + '?per_page=3').then((data)=>{
        let index = 0;
        if(data[0] !== undefined) {            
            for(const x of data) {
                if(++index  > 3 || cnum <= 0)
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
                `<div class="commit-container">
                <div class="commit-road"><a class="dots" href="${git_main_commits_url}">
                ${cnum >= 1  ? '<div class="dot"></div>'.repeat(3) : ''}` 
                + commits_road +
                `</div></div></div>`;
        }
    });
    
    var repoFormat =
        `<div class="row pt-1 pb-1 rounded repo"><div class="col-12 col-md-6 small text-left">
        <div class="toggle-plus"><span></span></div>
        <a class="alert no-text-deco text-dark text-capitalize" href="${repoInfo.html_url}" target="_blank">
        ${repoInfo.name}
        ${repoInfo.fork == true? '<sub class="text-primary">forked</sub>' : ''}</a>
        <span  class="badge badge-dark">${tag}</span>
        </div><div class="col-12 pt-2 pt-md-0 col-md-6 small text-muted text-left">
        ${repoInfo.description != null? repoInfo.description : "no description."}
        </div><div class="col-12 pt-2 pt-md-0 small commit-column" style="height:55px;">
        ${commits_road}
        </div></div></div>`;
    
    rposContainer.append(repoFormat);   
    
    
    addedRepos++;
    //when all repositories added, event get enabled.
    if(addedRepos === totalRepos) {
        // add click event
        $('.repo').on('click', function() {
            $($(this).children('div')[2]).slideToggle();
            $toggle = $($(this).find('.toggle-plus')[0]);
            if (!$toggle.hasClass('on')) {
                $toggle.addClass('on');
            } else {
                $toggle.removeClass('on');
            }
        });

        $repo = $($('.repo')[0]);

        $($repo.find('.toggle-plus')[0]).addClass('on');
        $($repo.children('div')[2]).slideDown();
    }
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