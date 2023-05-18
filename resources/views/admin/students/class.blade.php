@extends('admin.layouts.master')
@section('title', 'Học viên')

@section('content')
    <div class="animated fadeIn">
        <div class="content-header">
        </div>

        <div class="card">

            <div class="card-header">
                <h3 class="page-title d-inline mb-0">Thông tin lớp học của học viên</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>Mã học viên</th>
                                    <td>{{ $student->stu_id }}</td>
                                </tr>
                                <tr>
                                    <th>Họ và tên</th>
                                    <td>{{ $student->fist_name }} {{ $student->last_name }}</td>
                                </tr>

                                <tr>
                                    <th>Địa chỉ email</th>
                                    <td>{{ $student->email }}</td>
                                </tr>
                                <tr>
                                    <th>Lớp học</th>
                                    <td>
                                        @forelse ($classes as $class)
                                            <div class="card collapsed-card">
                                                <div class="card-header" style="font-size:1.3em">
                                                    <i class="fa fa-graduation-cap" style="font-size:1.4em"></i>
                                                    {{ $class['name'] }}
                                                    <div class="card-tools">
                                                        <button type="button" class="btn btn-tool"
                                                            data-card-widget="collapse">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <ul class="nav nav-pills flex-column">
                                                        <li class="nav-item">
                                                            @forelse ($class->courses()->get() as $course)
                                                                <div class="p-3">
                                                                    <i class="bi bi-journal-bookmark-fill"
                                                                        style="font-size:1.2em"></i>
                                                                    {{ $course->getOriginal('title') }}
                                                                    <br>
                                                                </div>
                                                            @empty
                                                                Lớp học không có khóa nào
                                                            @endforelse

                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        @empty
                                            <ul class="list-group list-group-flush"style="width : 100%">
                                                <li class="list-group-item">
                                                    Học viên chưa đăng kí lớp nào
                                                </li>
                                        @endforelse

                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


@stop
