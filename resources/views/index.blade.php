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

    <!--<div class="card overflow-hidden p-0">
        <table data-size="sm" tabindex="0" class="data-table">
            <thead>
            <tr>
                <th class="group from-column sortable-column">
                    <span>From</span>
                </th>
                <th class="group to-column pr-8">
                    <span>To</span>
                </th>
                <th class="actions-column"></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data as $key => $value)
                <tr class="redirect-row outline-none">
                    @foreach ($value as $key => $value)
                        @if($key === 'sites')
                            <td style="width:50%;">{{ implode(', ', $value) }}</td>
                        @elseif ($key !== 'id')
                            <td style="width:50%;">{{ $value }}</td>
                        @endif
                    @endforeach
                    <td><button class="btn" style="color: #bc2626;">Remove</button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>-->
@endsection
