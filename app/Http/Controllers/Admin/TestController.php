<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Test;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Admin\Test\StoreRequest;
use App\Http\Requests\Admin\Test\UpdateRequest;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author sant1ago
 *
 */
class TestController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        return view('admin.tests.index');
    }

    /**
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTestData()
    {
        $tests = Test::select([
            'id',
            'category',
            'total_score',
            'time',
            'title',
            'description',
        ])->with('courses')
            ->withCount('questions');

        return DataTables::of($tests)
            ->editColumn('category', function ($test) {
                if ($test->category == 0) return 'Bài thi cuối khoá';
                if ($test->category == 1) return 'Bài thi';
                return 'Khảo sát';
            })
            ->addColumn('category_name', function ($test) {
                $courseName = '';
                foreach ($test->courses as $courseItem) {
                    $courseName .= $courseItem->title . '<br/>';
                }
                return $courseName;
            })
            ->addColumn('total_score', function ($test) {
                $totalScore = $test->total_score;
                return $totalScore;
            })
            ->addColumn('actions', function ($test) {
                return view('admin.tests.actions', ['row' => $test])->render();
            })
            ->rawColumns(['actions', 'category_name'])
            ->make(true);
    }

    /**
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function create()
    {
        $user = Sentinel::getUser();
        $course = Course::select(['title', 'teacher_id', 'id'])->get();
        //dd($course);
        if ($user->inRole('teacher')) {
            $course = $course->where('teacher_id', $user->id);
        }
        // dd($course);
        $question   = Question::pluck('content', 'id');
        return view('admin.tests.create', compact('course', 'question'));
    }

    /**
     * @param StoreRequest $request
     * @throws ModelNotFoundException
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        $test = new Test();

        try {
            $course_id      = $request->course;
            $givenCategory  = $request->category;
            $course         = Course::with('tests')->find($course_id);
            $existingTests  = $course->tests;

            foreach ($existingTests as $existingTest) {
                if ($givenCategory == 0 && $existingTest->category == 0) {
                    return redirect()
                        ->back()
                        ->with('message', 'Khóa học này đã có bài thi cuối khoá')
                        ->with('type_alert', 'danger');
                }
            }
            $test->category     = $request->category;
            $test->title        = $request->title;
            $test->time         = $request->time;
            $test->description  = $request->description;
            $questionIds        = $request->question;
            $test->total_score  = Question::whereIn('id', $questionIds)->sum('score');
            $test->save();

            foreach ($questionIds as $id) {
                $question       = Question::find($id);
                $question->tests()->attach($test->id);
            }
            $course->tests()->attach($test->id);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw new ModelNotFoundException();
        }
        return redirect()->route('test.index');
    }

    /**
     * @param Request $request
     * @throws ModelNotFoundException
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request)
    {
        $id = $request->input('test_id');
        $test = Test::find($id);

        if ($test->users()->exists() == false) {
            $test->courses()->detach();
            $test->questions()->detach();

            Test::destroy($id);
            return redirect()
                ->action([TestController::class, 'index'])
                ->with('success', 'Dữ liệu xóa thành công.');
        } else {
            return redirect()
                ->action([TestController::class, 'index'])
                ->with('message', 'Bài test đang được sử dụng.')
                ->with('type_alert', "danger");
        }
    }

    /**
     * @param Request $request
     * @return NULL
     */
    public function getQuestion(Request $request)
    {
        $value = $request->get('value');
        if ($value == "#") {
            $questions = Question::all();
        } else {
            $questions = Question::where('course_id', $value)
                ->select('id', 'content', 'category')
                ->get();
        }
        $k = 1;
        $category = "";
        foreach ($questions as $row) {
            if ($row->category == 0) {
                $category = "Tự luận";
            } elseif ($row->category == 1) {
                $category = "Trắc nhiệm nhiều lựa chọn";
            } elseif ($row->category == 2) {
                $category = "Câu hỏi đúng sai";
            }
            $output = '<option name ="question_' . $row->id . '"  value="' . $row->id . '">' . $k . '. ' . $row->content . ' [' . $category . ']</option>';
            $k++;
            echo $output;
        }
    }

    /**
     * @param int $id
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function update($id)
    {
        $test  = Test::find($id);
        if ($test->users()->exists()) {
            return redirect()
                ->action([TestController::class, 'index'])
                ->with('message', 'Không thể sửa! Đã có học viên làm bài kiểm tra!')
                ->with('type_alert', 'danger');
        }
        $course     = Course::pluck('title', 'id');
        $question   = Question::pluck('content', 'id');

        return view('admin.tests.update', compact('course', 'question', 'test'));
    }

    /**
     * @param Request $request
     * @param int $id
     * @throws ModelNotFoundException
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveUpdates(UpdateRequest $request, $id)
    {
        $test       = Test::find($id);
        try {
            $test->title        = $request->title;
            $test->time         = $request->time;
            $test->description  = $request->description;
            $test->updated_at   = Carbon::now();
            $test->total_score  = $test->questions()->sum('score');
            $test->save();
        } catch (\Throwable $t) {
            DB::rollback();
            Log::info($t->getMessage());
            throw new ModelNotFoundException();
        }
        return redirect()->route('test.index');
    }

    /**
     * @param int $id
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function view($id)
    {
        $tests  = Test::find($id);
        $questions = $tests->questions;
        $arr_question = [];

        foreach ($questions as $question) {
            $arr_question[] = $question->pivot->question_id;
        }

        if ($arr_question == []) {
            $arr_question = "";
            $this->delete_test($id);
            return redirect()->route('test.index');
        } else {
            $arr_question = implode('-', $arr_question);
            $q_categories = [];
            $q_categories[0] = "Tự luận";
            $q_categories[1] = "Trắc nhiệm nhiều lựa chọn";
            $q_categories[2] = "Trắc nhiệm đúng sai";
            return view('admin.tests.questions.view_question', compact('tests', 'questions', 'arr_question', 'q_categories'));
        }
    }

    /**
     * @param int $id
     * @param int $testId
     * @param string $arr_quest
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function createquestion($id, $testId, $arr_quest)
    {
        $test = Test::find($testId);
        if ($test->users()->exists()) {
            return redirect(route('test.view', $testId))
                ->with('message', 'Không thể chỉnh sửa! Đã có học viên làm bài kiểm tra!')
                ->with('type_alert', 'danger');
        }
        $arr = explode("-", $arr_quest);
        $courses = Course::find($id);
        $questions = Question::where('course_id', $id)
            ->WhereNotIn('id', $arr)
            ->select('id', 'content', 'category')
            ->get();
        $q_categories = [];
        $q_categories[0] = "Tự luận";
        $q_categories[1] = "Trắc nhiệm nhiều lựa chọn";
        $q_categories[2] = "Trắc nhiệm đúng sai";
        return view('admin.tests.questions.create_question', compact('courses', 'questions', 'testId', 'q_categories'));
    }

    /**
     * @param Request $request
     * @param int $id_test
     * @throws ModelNotFoundException
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store_question(Request $request, $id_test)
    {
        $validated = $request->validate([
            'question' => 'required',
        ]);
        $test = Test::find($id_test);
        try {
            for ($q = 0; $q < (count($request->question)); $q++) {
                $question  = Question::find($request->question[$q]);
                $question->tests()->attach($test->id);
            }
        } catch (\Throwable $t) {
            DB::rollback();
            Log::info($t->getMessage());
            throw new ModelNotFoundException();
        }

        $test->total_score  = $test->questions()->sum('score');
        $test->save();
        return redirect()->route('test.view', $id_test);
    }

    /**
     * @param int $id_test
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete_question(Request $request, $id_test)
    {
        $test = Test::find($id_test);
        if ($test->users()->exists()) {
            return redirect(route('test.view', $id_test))
                ->with('message', 'Không thể chỉnh sửa! Đã có học viên làm bài kiểm tra!')
                ->with('type_alert', 'danger');
        }
        $id = $request->input('question_id', 'value');
        $question = Question::find($id);
        $question->tests()->detach($id_test);

        $test->total_score  = $test->questions()->sum('score');
        $test->save();
        return redirect()->route('test.view', $id_test);
    }

    /**
     * @param int $questionId
     * @param int $testId
     * @param int $courseId
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function question_edit($questionId, $testId, $courseId)
    {
        $test = Test::find($testId);
        if ($test->users()->exists()) {
            return redirect(route('test.view', $testId))
                ->with('message', 'Không thể chỉnh sửa! Đã có học viên làm bài kiểm tra!')
                ->with('type_alert', 'danger');
        }
        $question = Question::find($questionId);
        $questions = $test->questions;
        $questArray[] = "";
        foreach ($questions as $quest) {
            $questArray[] = $quest->pivot->question_id;
        }
        $question_old = Question::where('course_id', $courseId)
            ->WhereNotIn('id', $questArray)
            ->select('id', 'content', 'category')
            ->get();
        $categories = [];
        $categories[0] = "Tự luận";
        $categories[1] = "Trắc nhiệm nhiều lựa chọn";
        $categories[2] = "Trắc nhiệm đúng sai";
        return view('admin.tests.questions.edit_question', compact('test', 'question', 'question_old', 'categories'));
    }

    /**
     * @param Request $request
     * @param int $id, $id_question_old
     * @param int $id_question_old
     * @throws ModelNotFoundException
     * @return \Illuminate\Http\RedirectResponse
     */
    public function question_update(Request $request, $id, $id_question_old)
    {
        $test = Test::find($id);

        foreach ($test->questions as $row) {
            if ($row->pivot->question_id == $id_question_old) {
                $row->pivot->question_id = $request->question;
                $row->pivot->save();
            }
        }
        $test->total_score  = $test->questions()->sum('score');
        $test->save();
        return redirect()->route('test.view', $id);
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete_test($id)
    {
        $test = Test::find($id);
        $test->course()->detach();
        $test->question()->detach();
        Test::destroy($id);
        return redirect()
            ->action([TestController::class, 'index'])
            ->with('success', 'Dữ liệu xóa thành công.');
    }
}
