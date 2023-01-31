

document.querySelector('.sitemap-button').onclick = (e) => {


    e.preventDefault();

    createSitemap();

}

let links_counter = 0;

function createSitemap(){

    links_counter++;

    Ajax({data: {ajax:'sitemap', links_counter: links_counter}})
        .then((res) => {
            console.log('succ - ' + res);
        })
        .catch((res) => {
            console.log('error - ' + res);
            createSitemap();
        });

}


