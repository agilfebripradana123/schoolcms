<?php
use Illuminate\Support\Facades\Route;
Route::get("/re-registrants/export-dapodik", function(\Illuminate\Http\Request $request) {
    $id = $request->query("id");
    $ids = $request->query("ids");
    $q = App\Models\PPDB\ReRegistrant::query()
        ->where("re_registration_status", "completed")
        ->where("data_completed", true)
        ->whereNull("student_id");
    if ($id) $q->where("id", (int)$id);
    elseif ($ids) {
        $idList = array_filter(explode(",", (string)$ids), fn($v)=>$v!=="");
        $idList = array_map("intval", $idList);
        if ($idList!==[]) $q->whereIn("id", $idList);
    }
    $rows = $q->orderByDesc("re_registration_date")->get();
    $filename = "export-dapodik-".now()->format("Y-m-d-His").".xlsx";
    return \Maatwebsite\Excel\Facades\Excel::download(new App\Exports\DapodikRegistrantsExport($rows), $filename);
});
