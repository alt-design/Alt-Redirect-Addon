@extends('statamic::layout')

@section('content')
    <div id="alt-redirect-app" >
        <alt-redirect
            title="Alt Redirect"
            action="{{ cp_route('alt-redirect.create') }}"
            :blueprint='@json($blueprint)'
            :meta='@json($meta)'
            :values='@json($values)'
            :items="{{ json_encode($data) }}"
        ></alt-redirect>

        <!--  -->
    </div>


@endsection
