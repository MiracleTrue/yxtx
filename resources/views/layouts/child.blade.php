@extends('layouts.master')


@section('MyCss')
    <link rel="stylesheet" href="{{URL::asset('/css/***.css')}}">
@endsection
@section('content')
    <h1>网站首页</h1>
@endsection


@section('MyJs')
    <script type="text/javascript" src="{{URL::asset('/css/***.js')}}" ></script>
    <script type="text/javascript">
        /* code ...*/
    </script>
@endsection