

document.querySelector('.sitemap-button').onclick = (e) => {


    e.preventDefault();

    Ajax({type: 'POST'})
        .then((res) => {
            console.log('succ - ' + res);
        })
        .catch((res) => {
            console.log('error - ' + res);
        });


}



