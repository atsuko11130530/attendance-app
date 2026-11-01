<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// Request
use App\Http\Requests\AttendanceCorrectionRequest;

// Models
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceBreak;
use App\Models\CorrectionRequest;
use App\Models\ProposalBreak;

class AttendanceController extends Controller
{
    //FN023 勤怠一覧表示機能
    //ログインユーザーの月次勤怠一覧表示
    public function attendanceList(Request $request)
    {
        $date = $request->date
            ? Carbon::parse($request->date)
            : Carbon::now();

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $attendanceRecords = AttendanceRecord::where(
            'user_id',
            Auth::id()
        )
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->with('attendanceBreaks')
            ->get();

        $formattedAttendanceRecords = [];

        foreach ($attendanceRecords as $attendanceRecord) {
            $totalBreakMinutes = 0;
            $totalBreakTime = null;

            foreach ($attendanceRecord->attendanceBreaks as $break) {

                if ($break->break_in && $break->break_out) {

                    $breakIn = Carbon::parse($break->break_in);
                    $breakOut = Carbon::parse($break->break_out);

                    $totalBreakMinutes += $breakOut->diffInMinutes($breakIn);
                }
            }

                    $totalBreakTime = sprintf(
                        '%02d:%02d:00',
                        floor($totalBreakMinutes / 60),
                        $totalBreakMinutes % 60
                    );


                    $totalWorkTime = '';

                    if ($attendanceRecord->clock_in && $attendanceRecord->clock_out) {

                    $clockIn = Carbon::parse($attendanceRecord->clock_in);
                    $clockOut = Carbon::parse($attendanceRecord->clock_out);

                    $workMinutes =
                    $clockOut->diffInMinutes($clockIn)
                    - $totalBreakMinutes;

                    $totalWorkTime = sprintf(
                    '%02d:%02d:00',
                    floor($workMinutes / 60),
                    $workMinutes % 60
                    );
            }
                
            

            $formattedAttendanceRecords[] = [
                'id' => $attendanceRecord->id,
                'date' => $attendanceRecord->date,
                'clock_in' => $attendanceRecord->clock_in,
                'clock_out' => $attendanceRecord->clock_out,
                'total_break_time' => $totalBreakTime,
                'total_time' => $totalWorkTime,
            ];
            
        }

        return view(
            'user.user-attendance-list',
            compact(
                'date',
                'previousMonth',
                'nextMonth',
                'formattedAttendanceRecords'
            )
        );
    }

    /*
|--------------------------------------------------------------------------
| FN025 詳細画面遷移機能
| FN026 詳細情報取得機能
|--------------------------------------------------------------------------
|
| 勤怠一覧の「詳細」押下時に実行
|
| 取得する情報
| ・勤怠情報
| ・休憩情報
| ・承認待ち申請情報
|
| user.user-detailへ渡す
|
*/
    public function detail($id)
    {
        if (
            Auth::check() &&
            Auth::user()->admin_status
        ) 
        {
            return $this->adminDetail($id);
        }

        // 勤怠情報取得
        $attendance = AttendanceRecord::with(
            'attendanceBreaks'
        )
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // 承認待ち申請取得
        $application = CorrectionRequest::where(
            'attendance_record_id',
            $attendance->id
        )
            ->where(
                'approval_status',
                '承認待ち'
            )
            ->first();

        // ログインユーザー取得
        $user = Auth::user();

        // 休憩情報取得
        $breaks = [];

        foreach ($attendance->attendanceBreaks as $break) {

            $breaks[] = [
                'break_in' => $break->break_in,
                'break_out' => $break->break_out,
            ];
        }

        // Blade表示用データ作成
        $data = [
            'id' => $attendance->id,
            'application' => $application,
            'year' => Carbon::parse($attendance->date)->format('Y年'),
            'date' => Carbon::parse($attendance->date)->format('n月j日'),
            'clock_in' => $attendance->clock_in,
            'clock_out' => $attendance->clock_out,
            'breaks' => $breaks,
            'comment' => '',
            'raw_date' => $attendance->date,
        ];

        return view(
            'user.user-detail',
            compact(
                'user',
                'data'
            )
        );
    }
    /*
|--------------------------------------------------------------------------
| 勤怠登録画面表示
|--------------------------------------------------------------------------
|
| 出勤・退勤・休憩状態表示
|
*/    
    public function index()
    {
        $user = Auth::user();

        $formattedDate = Carbon::now()->format('Y年n月j日');

        $formattedTime = Carbon::now()->format('H:i');

        $status = '勤務外';

        $attendance = AttendanceRecord::where(
            'user_id',
            Auth::id()
        )
            ->whereDate('date', Carbon::today())
            ->latest('id')
            ->first();

        if ($attendance) {
            
            $status = $attendance->status;
        }
        
        return view(
            'user.attendance-register',
            compact(
                'user',
                'formattedDate',
                'formattedTime',
                'status',
                'attendance'
            )
        );
    }
    /*
|--------------------------------------------------------------------------
| 勤怠登録処理
|--------------------------------------------------------------------------
|
| ・出勤
| ・退勤
| ・休憩開始
| ・休憩終了
|
*/
    public function store(Request $request)
    {
        // 出勤処理
        if ($request->action === 'clock_in') {

            $todayAttendance = AttendanceRecord::where(
                'user_id',
                Auth::id()
            )
                ->whereDate(
                    'date',
                    Carbon::today()
                )
                ->first();

            if ($todayAttendance) {
                return redirect('/attendance');
            }

            AttendanceRecord::create([
                'user_id' => Auth::id(),
                'date' => Carbon::today(),
                'clock_in' => Carbon::now()->format('H:i:s'),
                'status' => '出勤中',
            ]);

            return redirect('/attendance');
        }

        // 休憩開始処理
        if ($request->action === 'break_in') {
            
            $todayAttendance = AttendanceRecord::where(
                'user_id',
                Auth::id()
            )
                ->whereDate(
                    'date',
                    Carbon::today()
                )
                ->latest('id')
                ->first();

            if (!$todayAttendance) {
                return redirect('/attendance');
            }
            
                AttendanceBreak::create([
                    'attendance_record_id' => $todayAttendance->id,
                    'break_in' => Carbon::now()->format('H:i:s'),
                ]);

                $todayAttendance->update([
                    'status' => '休憩中',
                ]);

                return redirect('/attendance');
            }

           

        // 休憩戻処理
        if ($request->action === 'break_out') {

            $todayAttendance = AttendanceRecord::where(
                'user_id',
                Auth::id()
            )
                ->whereDate(
                    'date',
                    Carbon::today()
                )
                ->latest('id')
                ->first();

            if (!$todayAttendance) {
                return redirect('/attendance');
            }

            $break = AttendanceBreak::where(
                'attendance_record_id',
                $todayAttendance->id
            )
                ->latest('id')
                ->first();

            $break->update([
                'break_out' => Carbon::now()->format('H:i:s'),
            ]);



            $todayAttendance->update([
                'status' => '出勤中',
            ]);

            return redirect('/attendance');
        }

        // 退勤処理
        if ($request->action === 'clock_out') 
            {

            $todayAttendance = AttendanceRecord::where(
                'user_id',
                Auth::id()
            )
                ->whereDate(
                    'date',
                    Carbon::today()
                )
                ->latest('id')
                ->first();

            if (!$todayAttendance) {
                return redirect('/attendance');
            }

            $todayAttendance->update([
                'clock_out' => Carbon::now()->format('H:i:s'),
                'status' => '退勤済',
            ]);

            return redirect('/attendance');

            }
    }

    // FN030 修正申請機能
    // 一般ユーザーは CorrectionRequest を作成
    // 管理者は AttendanceRecord を直接更新(FN040)
    public function update(
        AttendanceCorrectionRequest $request,
        $id
        ) 
        {
        // 管理者の場合 管理者は修正申請を作成しない
        // attendance_records を直接更新する
        if (Auth::user()->admin_status) {

            $attendance = AttendanceRecord::with(
                'attendanceBreaks'
            )->findOrFail($id);

            $attendance->update([
                'clock_in' => $request->new_clock_in,
                'clock_out' => $request->new_clock_out,
            ]);

            return redirect(
                '/admin/attendance/' . $id
            );
        }
        

        // 一般ユーザーの場合　直接更新しない
        // correction_requestsへ保存する
        $attendance = AttendanceRecord::with(
            'attendanceBreaks'
        )->findOrFail($id);

        $application = CorrectionRequest::where(
            'attendance_record_id',
            $attendance->id
        )
            ->where(
                'approval_status',
                '承認待ち'
            )
            ->first();      

        // 全角数字・全角コロンを半角へ変換
        $clockIn = mb_convert_kana(
            $request->new_clock_in,
            'as'
        );

        $clockOut = mb_convert_kana(
            $request->new_clock_out,
            'as'
        );

       
        $correctionRequest = CorrectionRequest::create([
            'user_id' => Auth::id(),
            'attendance_record_id' => $attendance->id,
            'approval_status' => '承認待ち',
            'application_date' => now()->toDateString(),
            'new_date' => $attendance->date,
            //'new_date' => $request->new_date,
            'new_clock_in' => $clockIn,
            'new_clock_out' => $clockOut,
            'comment' => $request->comment,
        ]);



        foreach ($request->new_break_in as $index => $breakIn) {

            $breakOut = $request->new_break_out[$index];

            if (empty($breakIn) || empty($breakOut)) {
                continue;
            }

            $breakIn = mb_convert_kana(
                $breakIn,
                'as'
            );

            $breakOut = mb_convert_kana(
                $breakOut,
                'as'
            );

            if (strlen($breakIn) === 5) {
                $breakIn .= ':00';
            }

            if (strlen($breakOut) === 5) {
                $breakOut .= ':00';
            }

            ProposalBreak::create([
                'correction_request_id' => $correctionRequest->id,
                'break_in' => $breakIn,
                'break_out' => $breakOut,
            ]);

            
        }


        

        return redirect(
            '/attendance/' . $id
        );
    }
    /*
|--------------------------------------------------------------------------
| FN031 承認待ち情報取得機能
| FN032 承認済み情報取得機能
|--------------------------------------------------------------------------
|
| ログインユーザーの申請一覧表示
|
| user.user-application-listへ渡す
|
*/
    public function applicationList()
    {
        $user = Auth::user();

        $applications = CorrectionRequest::where(
            'user_id',
            Auth::id()
        )->get();

        $formattedApplications = [];

        foreach ($applications as $application) {

            $formattedApplications[] = [
                'id' => $application->id,
                'approval_status' => $application->approval_status,
                'date' => Carbon::parse(
                    $application->new_date
                )->format('Y/m/d'),
                'comment' => $application->comment,
                'application_date' => Carbon::parse(
                    $application->application_date
                )->format('Y/m/d'),
            ];
        }

        return view(
            'user.user-application-list',
            compact(
                'user',
                'formattedApplications'
            )
        );
    }

    public function applicationDetail($id)
    {
        $application = CorrectionRequest::findOrFail($id);

        $user = User::find(
            $application->user_id
        );

        return view(
            'admin.admin-application-detail',
            compact(
                'application',
                'user'
            )
        );
        
    }

    /*
|--------------------------------------------------------------------------
| FN037 詳細情報取得機能
|--------------------------------------------------------------------------
|
| 管理者勤怠一覧の「詳細」押下時に実行
|
| 取得する情報
| ・スタッフ情報
| ・勤怠情報
| ・休憩情報
|
| admin.admin-detailへ渡す   
*/
    public function adminDetail($id)
    {
        //$attendance は「勤怠ID = $id の勤怠情報」と「その勤怠に紐づく休憩情報」が入った箱
        $attendance = AttendanceRecord::with(
            'attendanceBreaks'
        )->findOrFail($id);
        //管理者ログインであればadminDetailを実行する

        $user = User::find(
            $attendance->user_id
        );

        $breaks = [];

        foreach ($attendance->attendanceBreaks as $break) {

            $breaks[] = [
                'break_in' => $break->break_in,
                'break_out' => $break->break_out,
            ];
        }

        $attendanceRecord = [
            'id' => $attendance->id,
            'year' => Carbon::parse(
                $attendance->date
            )->format('Y年'),

            'date' => Carbon::parse(
                $attendance->date
            )->format('n月j日'),

            'clock_in' => $attendance->clock_in,
            'clock_out' => $attendance->clock_out,

            'breaks' => $breaks,

            'comment' => '',
        ];

        return view(
            'admin.admin-detail',
            compact(
                'user',
                'attendanceRecord'
            )
        );
    }

    // FN034
    // 日次勤怠一覧表示
    //
    // FN035
    // 前日・翌日切替
    //
    // FN036
    // スタッフ勤怠情報取得
    public function adminAttendanceList(Request $request)
    {
        $date = $request->date
            ? Carbon::parse($request->date)
            : Carbon::today();

        $previousDay = $date->copy()
            ->subDay()
            ->format('Y-m-d');

        $nextDay = $date->copy()
            ->addDay()
            ->format('Y-m-d');

        $users = User::all();

        $attendanceRecords = AttendanceRecord::whereDate(
            'date',
            $date
        )->get();

        return view(
            'admin.admin-attendance-list',
            compact(
                'date',
                'previousDay',
                'nextDay',
                'users',
                'attendanceRecords'
            )
        );
    }
    //管理者→申請一覧→admin-application-list.blade.php

    ///stamp_correction_request/listへアクセスすると実行されるメソッド
    public function adminApplicationList()
    {
        if (Auth::user()->admin_status) {

            // 管理者
            $applications = CorrectionRequest::all();

            return view(
                'admin.admin-application-list',
                compact('applications')
            );
        }


            // 一般ユーザー
            $applications = CorrectionRequest::where(
                'user_id',
                Auth::id()
            )->get();

            $formattedApplications = [];

        foreach ($applications as $application) {

            $formattedApplications[] = [
                'id' => $application->id,
                'approval_status' => $application->approval_status,
                'date' => Carbon::parse(
                    $application->new_date
                )->format('Y/m/d'),
                'comment' => $application->comment,
                'application_date' => Carbon::parse(
                    $application->application_date
                )->format('Y/m/d'),
            ];
        }
        
        $user = Auth::user();

                return view(
                'user.user-application-list',
                compact(
                'user',
                'formattedApplications'
                )
            );
    }

    //スタッフ一覧表示
        public function staffList()
        {
            $users = User::where(
                'admin_status',
             0
            )->get();

            return view(
                'admin.staff-list',
                compact('users')
            );

       
    }

    //FN43 勤怠一覧情報取得機能
    public function staffAttendance(Request $request, $id)
    {

        $user = User::findOrFail($id);

        $date = $request->date
            ? Carbon::parse($request->date)
            : Carbon::now();

        $previousMonth = $date->copy()
            ->subMonth()
            ->format('Y-m');

        $nextMonth = $date->copy()
            ->addMonth()
            ->format('Y-m');

        $attendanceRecords = AttendanceRecord::where(
            'user_id',
            $id
        )
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->with('attendanceBreaks')
            ->get();

        $formattedAttendanceRecords = [];

        foreach ($attendanceRecords as $attendanceRecord) {

            $formattedAttendanceRecords[] = [
                'id' => $attendanceRecord->id,
                'date' => $attendanceRecord->date,
                'clock_in' => $attendanceRecord->clock_in,
                'clock_out' => $attendanceRecord->clock_out,
                'total_break_time' => '',
                'total_time' => '',
                //$totalBreakTime と$totalWorkTime作ったら入れます
                
            ];  
        }
    
        return view(
            'admin.staff-attendance-list',
            compact(
                'user',
                'date',
                'previousMonth',
                'nextMonth',

                'formattedAttendanceRecords'
            )
        );
    }

    public function approve($id) 
    {
        $application = CorrectionRequest::findOrFail($id);

        $application->approval_status = '承認済み';

        $application->save();

        return redirect(
            '/stamp_correction_request/list'
        );

    }

    
    public function report()
    {
        $records = AttendanceRecord::with('attendanceBreaks')
            ->where('user_id', Auth::id())
            ->where('date', '>=', now()->subMonths(6))
            ->get();

        $totalWorkMinutes = 0;
        $totalOvertimeMinutes = 0;
        $workDays = 0;

        $lateCount = 0;
        $earlyLeaveCount = 0;
        $longWorkCount = 0;


        foreach ($records as $record) {

            if (!$record->clock_out) {
                continue;
            }

            if (
                Carbon::parse($record->clock_in)
                ->format('H:i') > '09:00'
            ) {
                $lateCount++;
            }

            if (
                Carbon::parse($record->clock_out)
                ->format('H:i') < '18:00'
            ) {
                $earlyLeaveCount++;
            }

            $workMinutes = Carbon::parse($record->clock_in)
                ->diffInMinutes(
                    Carbon::parse($record->clock_out)
                );

            if ($workMinutes > 600) {
                $longWorkCount++;
            }
                       
            $breakMinutes = 0;

            foreach ($record->attendanceBreaks as $break) {
                // 勤怠取得
                if ($break->break_out) {

                    $breakMinutes += Carbon::parse($break->break_in)
                        ->diffInMinutes(
                            Carbon::parse($break->break_out)
                        );
                }
            }
            // FN053 $summary 作成
            // 総労働時間
            // 総残業時間
            // 平均労働時間
            $workMinutes -= $breakMinutes;

            $totalWorkMinutes += $workMinutes;

            $totalOvertimeMinutes += max(
                0,
                $workMinutes - 480
            );

            $workDays++;
        }

        $avgWorkMinutes = $workDays > 0
            ? round($totalWorkMinutes / $workDays)
            : 0;
        
        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $avgWorkMinutes,
        ];

        $monthlyTrend = [];

        for ($i = 5; $i >= 0; $i--) {

            $month = now()
                ->subMonths($i)
                ->format('Y-m');

            $monthlyTrend[$month] = [
                'month' => $month,
                'work_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        foreach ($records as $record) {

            $month = Carbon::parse($record->date)
                ->format('Y-m');


            if ($record->clock_out) {

                $workMinutes = Carbon::parse($record->clock_in)
                    ->diffInMinutes(
                        Carbon::parse($record->clock_out)
                    );

                $monthlyTrend[$month]['work_minutes']
                    += $workMinutes;

                $monthlyTrend[$month]['overtime_minutes']
                    += max(
                        0,
                        $workMinutes - 480
                    );
            }
        }

        $monthlyTrend = array_values($monthlyTrend);

        

        // FN056 $anomalies 作成
        $anomalies = [
            'late_count' => $lateCount,
            'early_leave_count' => $earlyLeaveCount,
            'long_work_count' => $longWorkCount,
        ];

        return view(
            'reports.index',
            compact(
                'summary',
                'monthlyTrend',
                'anomalies'
            )
        );
    }
    
}