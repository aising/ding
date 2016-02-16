var Lock = function () {

    return {
        //main function to initiate the module
        init: function () {

             $.backstretch([
		        "media/image/bg/1.jpg",
		        "media/image/bg/2.jpg",
		        "media/image/bg/3.jpg",
		        "media/image/bg/4.jpg"
		        ], {
		          fade: 1000,
		          duration: 8000
		      });
        }

    };

}();