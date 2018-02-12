<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    	<meta http-equiv="X-UA-Compatible" content="IE=Edge，chrome=1">
    <title>首页</title>
    {{--<link rel="stylesheet" href="css/reset.css"/>--}}
    <link rel="stylesheet" href="{{asset('webStatic/css/reset.css')}}"/>
    {{--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">--}}
    {{--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css">--}}
    <script type="text/javascript" src="{{asset('webStatic/library/jquery/1.9.1/jquery.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('webStatic/library/layer-v3.1.0/layer/layer.js')}}"></script>
    
    <script type="text/javascript">
    	 window.networkState=true;
    	$(function(){
    		$("tr").last().css("border-bottom","1px solid #f5f5f5")
    		
    	
	
		$("table tr:nth-child(2n)").css("background","#ffffff")
		
    	})
    	
    	
    	
	
    	
 
    </script>
   
    <!--[if lt IE 9]>

	  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
	
	  <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
	
	<![endif]-->
@section('MyCss')
    @show
</head>
<body>
@yield('content')
</body>
@section('MyJs')
@show
</html>
