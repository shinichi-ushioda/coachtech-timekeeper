<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Http\Resources\AttendanceRecordResource;
use App\Http\Requests\Api\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\UpdateAttendanceRecordRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AttendanceRecordController extends Controller
{
    use AuthorizesRequests;

    /**
     * 勤怠一覧（GET /api/v1/attendance-records）
     */
    public function index(Request $request)
    {   
        $query = Attendance::query();

        // user_id で絞り込み
        if ($request->filled('user_id')){
            $query->where('user_id', $request->user_id);
        }

        // date(特定日)で絞り込み
        if ($request->filled('date')){
            $query->whereDate('work_date', $request->date);
        }

        // month(特定月)で絞り込み
        if($request->filled('month')){
            $query->where('work_date', 'like', $request->month . '%');
        }

        // per_page(デフォルト20、最大100)
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage > 100){
            $perPage = 100;
        }

        $records = $query->paginate($perPage);
        
        return response()->json([
            'data' => AttendanceRecordResource::collection($records->items()),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]); 
    }

    /**
     * 勤怠詳細（GET /api/v1/attendance-records/{attendanceRecord}）
     */
    public function show($attendanceRecord)
    {
        $record = Attendance::with(['user', 'breaks', 'correction'])->find($attendanceRecord);

        if (!$record){
            return response()->json([
                'error' => '勤怠情報が見つかりませんでした。',
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

            return new AttendanceRecordResource($record);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttendanceRecordRequest $request)
    {
        $date = $request->date;

        $attendance = Attendance::create([
            'user_id'   => $request->user_id,
            'work_date' => $date,
            'clock_in'  => $date . ' ' . $request->clock_in,
            'clock_out' => $request->clock_out ? $date . ' ' . $request->clock_out : null,
            'comment'   => $request->comment,
        ]);
            return response()->json([
                'data' => new AttendanceRecordResource($attendance),
            ], 201);
    }    


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttendanceRecordRequest $request, $attendanceRecord)
    {
        $record = Attendance::find($attendanceRecord);

        if (! $record) {
            return response()->json([
                'error' => '勤怠情報が見つかりませんでした。',
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        // 権限チェック：自分の勤怠でなければ403を返す
        $this->authorize('update', $record);

        $date = $request->date;

        $record->update([
            'user_id'   => $request->user_id,
            'work_date' => $date,
            // 日付と時刻を結合して datetime にする
            'clock_in'  => $date . ' ' . $request->clock_in,
            'clock_out' => $request->clock_out ? $date . ' ' . $request->clock_out : null,
            'comment'   => $request->comment,
        ]);

        return response()->json([
            'data' => new AttendanceRecordResource($record),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($attendanceRecord)
    {
        $record = Attendance::find($attendanceRecord);

        if(! $record){
            return response()->json([
                'error' => '勤怠情報が見つかりませんでした。',
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        // 権限チェック：自分の勤怠でなければ403を返す
        $this->authorize('delete', $record);        

        $record->delete();

        return response()->json(null, 204);
    }
}
