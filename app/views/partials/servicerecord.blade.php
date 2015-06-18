<h4 class="trmn my">Service Record</h4>
<h5 class="Incised901Light ninety">Last
    Updated: {{{ date('d M Y @ g:i A T', strtotime($user->updated_at)) }}}</h5>

<div id="user-profile">
    <div class="Incised901Bold">
        {{{ $user->getGreeting() }}} {{{ $user->first_name }}}{{{ isset($user->middle_name) ? ' ' . $user->middle_name : '' }}} {{{ $user->last_name }}}{{{ isset($user->suffix) ? ' ' . $user->suffix : '' }}}
    </div>
    <div class="NordItalic ninety padding-5">{{{$user->getPrimaryAssignmentName()}}} {{{$user->getPrimaryAssignmentDesignation()}}}</div>
    <div class="Incised901Light filePhoto">
        {{{$user->member_id}}}
        <div class="filePhotoBox">
            <div class="ofpt">
                Official<br/>File<br/>Photo
            </div>
        </div>
        {{{$user->getPrimaryBillet()}}}<br/>
        <div class="Incised901Light seventy-five">Assigned: {{{$user->getPrimaryDateAssigned()}}}</div>
    </div>
    <div class="Incised901Black ninety">
        Time In Grade: {{{$user->getTimeInGrade()}}}
    </div>
    <div class="Incised901Black ninety">
        Time In Service: {{{$user->getTimeInService()}}}
    </div>
    <div class="Incised901Black ninety">
        Awards:
    </div>

    <div class="Incised901Black ninety">
        Academy Coursework:
        <h5 class="Incised901Light ninety">Last
            Updated: {{{ date('d M Y @ g:i A T', strtotime($user->getExamLastUpdated())) }}}</h5>
        @foreach($user->getExamList() as $exam => $gradeInfo)
            <div class="row">
                <div class="small-1 columns Incised901Light ninety">&nbsp;</div>
                <div class="small-2 columns Incised901Light ninety textLeft">{{{$exam}}}</div>
                <div class="small-2 columns Incised901Light ninety textRight">{{{$gradeInfo['score']}}}</div>
                <div class="small-2 columns Incised901Light ninety end textRight">{{{$gradeInfo['date']}}}</div>
            </div>
        @endforeach
    </div>
    <div class="Incised901Black ninety">
        Contact:
        <div class="row">
            <div class="small-1 columns Incised901Light ninety">&nbsp;</div>
            <div class="small-10 columns Incised901Light ninety textLeft end">
                {{{ $user->address_1 }}}<br/>
                @if(!empty($user->address_2))
                    {{{ $user->address_2 }}}<br/>
                @endif
                {{{ $user->city }}}, {{{ $user->state_province }}} {{{ $user->postal_code }}}<br/>
                {{{ $user->email_address }}}<br/>
                {{ isset($user->phone_number) ? $user->phone_number . '<br />' : '' }}
            </div>
        </div>
        <div class="row">
            <div class="small-1 columns Incised901Light ninety">&nbsp;</div>
            <div class="small-10 columns Incised901Light ninety textLeft end">
                <br/><a href="{{route('user.edit', [$user->_id])}}" class="editButton Incised901Black margin-5">EDIT</a>
            </div>
        </div>
    </div>
</div>