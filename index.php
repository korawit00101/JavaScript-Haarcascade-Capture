<html lang="en">
<head>
	<style>
	.box{
		border: 2px black solid;
		padding: 10px;
		width: fit-content;
	}

	.bar{
		border: 1px gray solid;
		background-color: antiquewhite;
		width: 100%;
		height: 20px;
	}   

	.fill 
	{   
		background-color: rgb(21, 255, 0);
		width: 0%;
		height: 20px;
		display: block;
		transition: 1s;
	}
	</style>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opencv JS</title>
    <script async src="js/opencv.js" onload="openCvReady();"></script>
    <script src="js/utils.js"></script>
	<script src="js/jquery-1.11.0.min.js"></script>
</head>
<body>
    <video id="cam_input" height="480" width="640"></video>
	<canvas id="canvas_output"></canvas>
	<canvas id="captured"></canvas>
	<button onclick="takePic()">Take a picture</button>
	<div class="bar">
	<span class="fill" id="progressBar"><span id="status"></span></span>
	</div>
</body>
<script type="text/JavaScript">
var take;
function takePic()
{
	console.log("Captured")
	take = true;
}

function openCvReady() {
  let count = 0;
  cv['onRuntimeInitialized']=()=>{
    let video = document.getElementById("cam_input"); // video is the id of video tag
    navigator.mediaDevices.getUserMedia({ video: true, audio: false })
    .then(function(stream) {
        video.srcObject = stream;
        video.play();
    })
    .catch(function(err) {
        console.log("An error occurred! " + err);
    });
    let src = new cv.Mat(video.height, video.width, cv.CV_8UC4);
    let dst = new cv.Mat(video.height, video.width, cv.CV_8UC4);
    let gray = new cv.Mat();
	
	let face_cropped = new cv.Mat(video.height, video.width, cv.CV_8UC4);
	let face_resize = new cv.Mat(video.height, video.width, cv.CV_8UC4);
	
    let cap = new cv.VideoCapture(cam_input);
    let faces = new cv.RectVector();
    let classifier = new cv.CascadeClassifier();
    let utils = new Utils('errorMessage');
    let faceCascadeFile = 'haarcascade_frontalface_default.xml'; // path to xml
    utils.createFileFromUrl(faceCascadeFile, faceCascadeFile, () => {
    classifier.load(faceCascadeFile); // in the callback, load the cascade from file 
	});
    const FPS = 24;
	
	let start_time = Date.now();
    function processVideo() {
		var canvas_output = document.getElementById('canvas_output');
		var captured = document.getElementById('captured');
			let begin = Date.now();
			cap.read(src);
			src.copyTo(dst);
			cv.cvtColor(dst, gray, cv.COLOR_RGBA2GRAY, 0);
			try{
				classifier.detectMultiScale(gray, faces, 1.1, 3, 0);
				console.log(faces.size());
			}catch(err){
				console.log(err);
			}
			for (let i = 0; i < faces.size(); ++i) {
				let face = faces.get(i);
				let point1 = new cv.Point(face.x, face.y);
				let point2 = new cv.Point(face.x + face.width, face.y + face.height);
				//let rect = new cv.Rect(face.x, face.y, face.x + face.width, face.y  + face.height);
				//console.log(rect);
				face_cropped = src.roi(face);
				cv.resize(face_cropped, face_resize, new cv.Size(256, 256), 0, 0, cv.INTER_AREA); 
				//console.log('image width: ' + face_resize.cols + '\n' +'image height: ' + face_resize.rows + '\n');
				cv.rectangle(dst, point1, point2, [255, 0, 0, 255]);
				cv.imshow("canvas_output", dst);
			}
		if(count < 5 && take == true){
			if(faces.size() > 0){
				if((Date.now() - start_time) >= 1000){
					start_time = Date.now();
					cv.imshow("captured", face_resize);
					try{
						var  dataURL = canvas_output.toDataURL();
						$.ajax({

							xhr: function() {
							var xhr = new window.XMLHttpRequest();
							xhr.upload.addEventListener("progress", function(evt) {
								if (evt.lengthComputable) {
									var percentComplete = Math.round((evt.loaded / evt.total)) * 100;
									document.getElementById('progressBar').style.width = percentComplete+"%";
									document.getElementById('status').innerHTML = percentComplete+"%   "+count+"/"+"5";
								}
							}, false)
							return xhr;
							},
								type: "POST",
								url: "http://localhost/facedection/uploadface.php",//change this before run
								data: { 
								   imgBase64: dataURL
								},
								beforeSend: function(){
									document.getElementById('progressBar').style.width = 0+"%";
									document.getElementById('status').innerHTML = 0+"%   "+count+"/"+"5";
								}
							}).done(function(response) {
								console.log('saved: ' + response); 
						});
						count = count + 1;
					}catch(err){
						console.log(err);
					}
				}
			}take = false;
		}else{
			;
		}

        // schedule next one.
        let delay = 1000/FPS - (Date.now() - begin);
        setTimeout(processVideo, delay);
	}
// schedule first one.
setTimeout(processVideo, 0);
  };
}
</script>
</html>
