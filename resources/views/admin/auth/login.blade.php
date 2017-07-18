@extends('layouts.admin-login')

@section('content')
    <div class="row">

        <div class="col-md-7">

            <div class="signin-info">
                <div class="logopanel">
                    <h1><span>[</span> 万豪会彩票管理系统 <span>]</span></h1>
                </div><!-- logopanel -->

                <div class="mb20"></div>

                <h5><strong>欢迎您！</strong></h5>

                <div class="mb20"></div>
                <strong>Not a member? <a href="{{ url('/admin/register') }}">Sign Up</a></strong>
            </div><!-- signin0-info -->

        </div><!-- col-sm-7 -->

        <div class="col-md-5">
            <form method="post" action="{{ url('/admin/login') }}">
                @foreach($errors->all() as $error)
                    <li class="list-group-item list-group-item-danger">{{$error}}</li>
                @endforeach

                @if($errors->first())
                    <div class="alert alert-danger">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <strong>{{ $errors->first() }}!</strong>
                    </div>
                @endif
                <h4 class="nomargin">登陆</h4>
                <input type="text" name="username" class="form-control uname" placeholder="用户名"/>
                @if ($errors->has('username'))
                    <span class="help-block">
                        <strong>{{ $errors->first('username') }}</strong>
                    </span>
                @endif
                <input type="password" name="password" class="form-control pword" placeholder="密码"/>
                @if ($errors->has('password'))
                    <span class="help-block">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                @endif
                <div class="checkbox">
                    <input type="checkbox" name="remember"> 记住我
                </div>
                <a href="#">
                    <small>忘记密码?</small>
                </a>
                <input type="hidden" class="form-control" name="_token" value="{{ csrf_token() }}">
                <button type="submit" class="btn btn-success btn-block">登陆</button>
            </form>
        </div><!-- col-sm-5 -->

    </div><!-- row -->
@endsection