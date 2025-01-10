@extends('statamic::layout')

@section('content')
    <div id="alt-redirect-app" >
        <alt-redirect
            title="{{ $title }}"
            instructions="{{ $instructions }}"
            action="{{ cp_route($action) }}"
            :blueprint='@json($blueprint)'
            :meta='@json($meta)'
            :values='@json($values)'
            :items="{{ json_encode($data) }}"
            type="{{ $type }}"
        ></alt-redirect>

        <!--  -->
    </div>


@endsection
