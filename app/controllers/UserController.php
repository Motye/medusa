<?php

class UserController extends \BaseController
{

    /**
     * Display a listing of users
     *
     * @return Response
     */
    public function index()
    {
        $users = User::all();

        return View::make( 'user.index', [ 'users' => $users ] );
    }

    /**
     * Show the form for creating a new user
     *
     * @return Response
     */
    public function create()
    {
        return View::make( 'user.create', [ 'user' => new User ] );
    }

    /**
     * Store a newly created user in storage.
     *
     * @return Response
     */
    public function store()
    {

        $validator = Validator::make( $data = Input::all(), User::$rules, User::$error_message );

        if ( $validator->fails() ) {
            return Redirect::back()->withErrors( $validator )->withInput();
        }

        // Massage the data a little bit.  First, build up the rank array

        $rank = [ ];

        if ( isset( $data[ 'permanent_rank' ] ) === true && empty( $data[ 'permanent_rank' ] ) === false ) {
            $rank[ 'permanent_rank' ] = [ 'grade' => $data[ 'permanent_rank' ], 'date_of_rank' => date( 'Y-m-d', strtotime( $data[ 'perm_dor' ] ) ) ];
            unset( $data[ 'permanent_rank' ], $data[ 'perm_dor' ] );
        }

        if ( isset( $data[ 'brevet_rank' ] ) === true && empty( $data[ 'brevet_rank' ] ) === false ) {
            $rank[ 'brevet_rank' ] = [ 'grade' => $data[ 'brevet_rank' ], 'date_of_rank' => date( 'Y-m-d', strtotime( $data[ 'brevet_dor' ] ) ) ];
            unset( $data[ 'brevet_rank' ], $data[ 'brevet_dor' ] );
        }

        $data[ 'rank' ] = $rank;

        // Build up the member assignments

        $chapterName = Chapter::find( $data[ 'primary_assignment' ] )->chapter_name;

        $assignment[ ] = [
            'chapter_id' => $data[ 'primary_assignment' ],
            'chapter_name' => $chapterName,
            'date_assigned' => date( 'Y-m-d', strtotime( $data[ 'primary_date_assigned' ] ) ),
            'billet' => $data[ 'primary_billet' ],
            'primary' => true
        ];

        unset( $data[ 'primary_assignment' ], $data[ 'primary_date_assigned' ], $data[ 'primary_billet' ] );

        if ( isset( $data[ 'secondary_assignment' ] ) === true && empty( $data[ 'secondary_assignment' ] ) === false ) {
            $chapterName = Chapter::find( $data[ 'secondary_assignment' ] )->chapter_name;

            $assignment[ ] = [
                'chapter_id' => $data[ 'secondary_assignment' ],
                'chapter_name' => $chapterName,
                'date_assigned' => date( 'Y-m-d', strtotime( $data[ 'secondary_date_assigned' ] ) ),
                'billet' => $data[ 'secondary_billet' ],
                'primary' => false
            ];

            unset( $data[ 'secondary_assignment' ], $data[ 'secondary_date_assigned' ], $data[ 'secondary_billet' ] );
        }

        $data[ 'assignment' ] = $assignment;

        // Hash the password

        $data[ 'password' ] = Hash::make( $data[ 'password' ] );

        // For future use

        $data[ 'peerage_record' ] = [ ];

        $data[ 'awards_record' ] = [ ];

        $data[ 'exam_record' ] = [ ];

        unset( $data[ '_token' ], $data[ 'password_confirmation' ] );

        $user = User::create( $data );

        Event::fire( 'user.created', $user );

        return Redirect::route( 'user.index' );
    }

    /**
     * Store a new applicant
     *
     * @return Response
     */
    public function apply()
    {
        $rules = [
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'address_1' => 'required|min:4',
            'city' => 'required|min:2',
            'state_province' => 'required|min:2',
            'postal_code' => 'required|min:2',
            'country' => 'required',
            'email_address' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ];

        $validator = Validator::make( $data = Input::all(), $rules );

        if ( $validator->fails() ) {
            return Redirect::back()->withErrors( $validator )->withInput();
        }

        $memberId = $this->getNextAvailableMemberId();

        $data[ 'member_id' ] = $data[ 'branch' ] . $memberId;

        $rank = [
            'permanent_rank' => [ 'grade' => 'E1', 'date_of_rank' => date( 'Y-m-d' ) ],
            'brevet_rank' => [ 'grade' => 'E1', 'date_of_rank' => date( 'Y-m-d' ) ],
        ];

        $data[ 'rank' ] = $rank;

        $assignment = [];

        if ( isset( $data[ 'primary_assignment' ] ) && !empty( $data[ 'primary_assignment' ] ) ) {
            $chapterName = Chapter::find( $data[ 'primary_assignment' ] )->chapter_name;

            $assignment[ ] = [
                'chapter_id' => $data[ 'primary_assignment' ],
                'chapter_name' => $chapterName,
                'date_assigned' => date( 'Y-m-d' ),
                'billet' => '',
                'primary' => true
            ];

            unset( $data[ 'primary_assignment' ], $data[ 'primary_date_assigned' ], $data[ 'primary_billet' ] );
        }

        $data[ 'assignment' ] = $assignment;

        // Hash the password

        $data[ 'password' ] = Hash::make( $data[ 'password' ] );

        // For future use

        $data[ 'peerage_record' ] = [ ];
        $data[ 'awards_record' ] = [ ];
        $data[ 'exam_record' ] = [ ];

        unset( $data[ '_token' ], $data[ 'password_confirmation' ] );

        $user = User::create( $data );

        Event::fire( 'user.registered', $user );

        return Redirect::route( 'home' )->with( 'message', 'User created. You may now log in!' );
    }

    /**
     * Display the specified user.
     *
     * @param  User $user
     * @return Response
     */
    public function show( User $user )
    {
        $greeting = $user->getGreeting();

        return View::make( 'user.show', [
            'user' => $user,
            'greeting' => $greeting,
            'countries' => $this->_getCountries(),
            'branches' => Branch::getBranchList(),
            'permRank' => $user->perm_display,
            'brevetRank' => $user->brevet_display ] );
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  User $user
     * @return Response
     */
    public function edit( User $user )
    {
        $greeting = $user->getGreeting();
        $greeting = $user->getGreeting();

        if ( isset( $user->rating ) === true && empty( $user->rating ) === false ) {
            $user->rating = [ 'rate' => $user->rating, 'description' => Rating::where( 'rate_code', '=', $user->rating )->get()[ 0 ]->rate[ 'description' ] ];
        }

        $user->permanent_rank = $user->rank[ 'permanent_rank' ][ 'grade' ];

        $user->perm_dor = $user->rank[ 'permanent_rank' ][ 'date_of_rank' ];

        if ( empty( $user->rank[ 'brevet_rank' ][ 'grade' ] ) === false ) {
            $user->brevet_rank = $user->rank[ 'brevet_rank' ][ 'grade' ];
            $user->brevet_dor = $user->rank[ 'brevet_rank' ][ 'date_of_rank' ];
        }

        $user->primary_assignment = $user->getPrimaryAssignmentId();
        $user->primary_billet = $user->getPrimaryBillet();
        $user->primary_date_assigned = $user->getPrimaryDateAssigned();

        return View::make( 'user.edit', [
                'user' => $user,
                'greeting' => $greeting,
                'countries' => $this->_getCountries(),
                'branches' => Branch::getBranchList(),
                'grades' => Grade::getGradesForBranch( $user->branch ),
                'ratings' => Rating::getRatingsForBranch( $user->branch ),
                'chapters' => Chapter::getChapters(),
            ]
        );
    }

    /**
     * Update the specified user in storage.
     *
     * @param  User $user
     * @return Response
     */
    public function update( User $user )
    {
        $validator = Validator::make( $data = Input::all(), User::$updateRules, User::$error_message );

        if ( $validator->fails() ) {
            return Redirect::back()->withErrors( $validator )->withInput();
        }

        // Massage the data a little bit.  First, build up the rank array

        $rank = [ ];

        if ( isset( $data[ 'permanent_rank' ] ) === true && empty( $data[ 'permanent_rank' ] ) === false ) {
            $rank[ 'permanent_rank' ] = [ 'grade' => $data[ 'permanent_rank' ], 'date_of_rank' => date( 'Y-m-d', strtotime( $data[ 'perm_dor' ] ) ) ];
            unset( $data[ 'permanent_rank' ], $data[ 'perm_dor' ] );
        }

        if ( isset( $data[ 'brevet_rank' ] ) === true && empty( $data[ 'brevet_rank' ] ) === false ) {
            $rank[ 'brevet_rank' ] = [ 'grade' => $data[ 'brevet_rank' ], 'date_of_rank' => date( 'Y-m-d', strtotime( $data[ 'brevet_dor' ] ) ) ];
            unset( $data[ 'brevet_rank' ], $data[ 'brevet_dor' ] );
        }

        $data[ 'rank' ] = $rank;

        // Build up the member assignments

        $chapterName = Chapter::find( $data[ 'primary_assignment' ] )->chapter_name;

        $assignment[ ] = [
            'chapter_id' => $data[ 'primary_assignment' ],
            'chapter_name' => $chapterName,
            'date_assigned' => date( 'Y-m-d', strtotime( $data[ 'primary_date_assigned' ] ) ),
            'billet' => $data[ 'primary_billet' ],
            'primary' => true
        ];

        unset( $data[ 'primary_assignment' ], $data[ 'primary_date_assigned' ], $data[ 'primary_billet' ] );

        if ( isset( $data[ 'secondary_assignment' ] ) === true && empty( $data[ 'secondary_assignment' ] ) === false ) {
            $chapterName = Chapter::find( $data[ 'secondary_assignment' ] )->chapter_name;

            $assignment[ ] = [
                'chapter_id' => $data[ 'secondary_assignment' ],
                'chapter_name' => $chapterName,
                'date_assigned' => date( 'Y-m-d', strtotime( $data[ 'secondary_date_assigned' ] ) ),
                'billet' => $data[ 'secondary_billet' ],
                'primary' => false
            ];

            unset( $data[ 'secondary_assignment' ], $data[ 'secondary_date_assigned' ], $data[ 'secondary_billet' ] );
        }

        $data[ 'assignment' ] = $assignment;

        // Hash the password

        $data[ 'password' ] = Hash::make( $data[ 'password' ] );

        // For future use

        $data[ 'peerage_record' ] = [ ];

        $data[ 'awards_record' ] = [ ];

        $data[ 'exam_record' ] = [ ];

        unset( $data[ '_method' ], $data[ '_token' ], $data[ 'password_confirmation' ] );

        $user->update( $data );

        return Redirect::route( 'user.index' );
    }

    /**
     * Confirm that the user should be deleted.
     *
     * @param  User $user
     * @return Response
     */
    public function confirmDelete( User $user )
    {
        return View::make( 'user.confirm-delete', [ 'user' => $user ] );
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  User $user
     * @return Response
     */
    public function destroy( User $user )
    {
        User::destroy( $user->_id );

        return Redirect::route( 'user.index' );
    }

    public function register()
    {
        $fullCountryList = Countries::getList();
        $countries = [ ];

        foreach( $fullCountryList as $country ) {
            $countries[ $country[ 'iso_3166_3' ] ] = $country[ 'name' ];
        }

        asort( $countries );

        $fullChapterList = Chapter::all();
        $chapters = [ ];

        foreach( $fullChapterList as $chapter ) {
            $chapters[ $chapter[ '_id' ] ] = $chapter[ 'chapter_name' ];
        }

        asort( $chapters );

        $finalChapters = array_merge( [ '' => 'Select a Chapter' ], $chapters );

        $fullBranchList = Branch::all();
        $branches = [ ];

        foreach( $fullBranchList as $branch ) {
            $branches[ $branch[ 'branch' ] ] = $branch[ 'branch_name' ];
        }

        asort( $branches );

        $viewData = [
            'user' => new User,
            'countries' => $countries,
            'branches' => $branches,
            'chapters' => $finalChapters,
        ];

        return View::make( 'user.register', $viewData );
    }

    private function _getCountries()
    {
        $results = Countries::getList();
        $countries = [ ];

        foreach ( $results as $country ) {
            $countries[ $country[ 'iso_3166_3' ] ] = $country[ 'name' ];
        }

        return $countries;
    }

    public function getNextAvailableMemberId()
    {
        $memberIds = User::lists( 'member_id' );
        $uniqueMemberIds = [ ];

        foreach( $memberIds as $memberId ) {
            $uniqueMemberIds[ ] = intval( substr( $memberId, 4, 4 ) );
        }

        if ( sizeof( $uniqueMemberIds ) == 0 ) {
            return "-0000-" . date( 'y' );
        }

        asort( $uniqueMemberIds );

        $lastUsedId = array_pop( $uniqueMemberIds );

        $newNumber = $lastUsedId + 1;

        if ( $newNumber > 9999 ) {
            $newNumber = 0;
        }

        $newNumber = str_pad( $newNumber, 4, '0', STR_PAD_LEFT );

        $yearCode = date( 'y' );

        return "-$newNumber-$yearCode";
    }

}
