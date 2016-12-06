@extends('layouts.app')


@section('content')

    <div class="container">

        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading">Agregar XMLs</div>
                    <div class="panel-body">
                      {{--<form method="POST" action="http://diamond-chaos.codio.io:3000/tuto/public/storage/create" accept-charset="UTF-8" enctype="multipart/form-data">--}}
                        <form class="form-horizontal form-login" role="form" method="POST" action="{{ url('/saveUpXml') }}"  accept-charset="UTF-8" enctype="multipart/form-data" >
                                {{ csrf_field() }}

                            <input type="hidden" name="_token" value="{{ csrf_token() }}">

                            <div class="form-group">
                                <label class="col-md-4 control-label">Nuevo Archivo</label>
                                <div class="col-md-6">
                                    <input type="file" class="form-control" name="file" >
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-4">
                                    <button type="submit" class="btn btn-primary">Enviar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection