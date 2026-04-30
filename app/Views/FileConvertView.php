<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voucher Import System</title>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/DropZoneContainer.css'); ?>">
    <style>
        body { background-color: #e9ecef; font-family: "Inter", -apple-system, sans-serif; }
        
        .upload-card { 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            width: 550px;
            margin: 80px auto;
            border: none;
            overflow: hidden; /* Ensures footer corners match card */
        }

        .card-header { 
            background: none; 
            padding: 20px 25px 10px; 
            border: none; 
            position: relative; 
        }

        .card-header h5 { margin: 0; font-weight: 700; color: #333; }

        .card-header .close-icon { 
            position: absolute; 
            top: 20px; 
            right: 25px; 
            color: #aaa; 
            font-size: 20px; 
            cursor: pointer; 
        }

        .card-header p { margin: 8px 0 0; color: #777; font-size: 0.85rem; }

        .card-body { padding: 0 25px 25px; }

        .card-footer { 
            padding: 15px 25px; 
            background-color: #fff; 
            border-top: 1px solid #f0f0f0; 
            display: flex; 
            justify-content: flex-end;
            align-items: center;
        }

        .btn-cancel { background: none; border: none; color: #666; font-weight: 600; margin-right: 20px; outline: none !important; cursor: pointer; }
        
        .btn-submit { 
            background-color: #0095ff; 
            color: #fff; 
            border: none; 
            padding: 10px 25px; 
            border-radius: 6px; 
            font-weight: 600; 
            transition: 0.2s; 
            cursor: pointer;
        }
        
        .btn-submit:hover { background-color: #0077cc; }

        .alert { border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="upload-card">
        <!-- Header with Close Icon -->
        <div class="card-header">
            <span class="close-icon" onclick="window.history.back();">&times;</span>
            <h5>Upload Files</h5>
            <p>You can only upload 1 file at a time. Select Excel/CSV File</p>
        </div>
        
        <form action="<?= base_url('import-data') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?= ($status == 'success') ? 'success' : 'danger' ?> py-2 small">
                        <?= esc($message) ?>
                    </div>
                <?php endif; ?>

                <!-- If using your custom DropZoneContainer.css, keep that structure here -->
                <div class="form-group">                    
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx, .xls, .csv" required>
                </div>
            </div>
            
            <!-- Footer with Cancel and Upload buttons -->
            <div class="card-footer">
                <button type="button" class="btn-cancel" onclick="window.history.back();">Cancel</button>
                <button type="submit" class="btn-submit">Upload Files</button>
            </div>
        </form>
    </div>

</body>
</html>