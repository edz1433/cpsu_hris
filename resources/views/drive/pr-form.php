@extends('layouts.master')

@section('body')
<link rel="stylesheet" href="{{ asset('css/subfolder.css') }}">
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2">
            @include('Drive.submenu')
        </div>
        @include('Drive.modals')
        <div class="col-lg-10">
            <div class="card card-info card-outline">   
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-success1"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('drive') }}" class="text-success1">My Drive</a></li>
                    
                    @foreach($connFolders as $connFolder)
                        <li class="breadcrumb-item"><a href="{{ route('sub-folder', $connFolder->id) }}" class="text-success1">{{ $connFolder->folder_name }}</a></li>
                    @endforeach
                    <li class="breadcrumb-item active text-muted" aria-current="page">{{ $folder->folder_name }}</li>
                </ol>
            </nav>
                <div class="card-body table-responsive p-0" style="height: 400px;">
                    <table class="table table-head-fixed text-nowrap">
                        <tbody>
                          
                        </tbody>
                    </table>
                </div>
            </div>  
        </div>
    </div>
</div>
@endsection
